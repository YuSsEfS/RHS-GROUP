<?php

namespace App\Services;

use App\Models\Cv;

class CvDuplicateDetectionService
{
    public function findLikelyDuplicate(array $payload, ?int $ignoreCvId = null): ?array
    {
        $normalized = $this->normalizePayload($payload);

        if (!empty($normalized['file_hash'])) {
            $exactHashMatch = Cv::query()
                ->when($ignoreCvId, fn ($query) => $query->whereKeyNot($ignoreCvId))
                ->where('file_hash', $normalized['file_hash'])
                ->first();

            if ($exactHashMatch) {
                return $this->buildResult($exactHashMatch, 100, ['hash_exact']);
            }
        }

        if (!empty($normalized['email'])) {
            $exactEmailMatch = Cv::query()
                ->when($ignoreCvId, fn ($query) => $query->whereKeyNot($ignoreCvId))
                ->whereRaw('LOWER(email) = ?', [$normalized['email']])
                ->first();

            if ($exactEmailMatch) {
                return $this->buildResult($exactEmailMatch, 96, ['email_exact']);
            }
        }

        if (!empty($normalized['phone'])) {
            $phoneCandidates = Cv::query()
                ->when($ignoreCvId, fn ($query) => $query->whereKeyNot($ignoreCvId))
                ->whereNotNull('phone')
                ->where('phone', 'like', '%' . substr($normalized['phone'], -8) . '%')
                ->limit(20)
                ->get();

            foreach ($phoneCandidates as $candidate) {
                if ($this->normalizePhone($candidate->phone) === $normalized['phone']) {
                    return $this->buildResult($candidate, 93, ['phone_exact']);
                }
            }
        }

        if (
            empty($normalized['name'])
            && empty($normalized['filename'])
            && empty($normalized['email_name'])
        ) {
            return null;
        }

        $hasLocator = !empty(array_filter($normalized['name_tokens'], static fn ($token) => mb_strlen($token) >= 3))
            || !empty(array_filter($normalized['email_name_tokens'], static fn ($token) => mb_strlen($token) >= 3))
            || !empty($normalized['filename_core'])
            || !empty($normalized['title'])
            || !empty($normalized['city'])
            || !empty($normalized['email']);

        if (!$hasLocator) {
            return null;
        }

        $candidates = Cv::query()
            ->when($ignoreCvId, fn ($query) => $query->whereKeyNot($ignoreCvId))
            ->where(function ($query) use ($normalized) {
                $applied = false;

                foreach ($normalized['name_tokens'] as $token) {
                    if (mb_strlen($token) < 3) {
                        continue;
                    }

                    $query->orWhere('candidate_name', 'like', '%' . $token . '%');
                    $applied = true;
                }

                foreach ($normalized['email_name_tokens'] as $token) {
                    if (mb_strlen($token) < 3) {
                        continue;
                    }

                    $query->orWhere('candidate_name', 'like', '%' . $token . '%');
                    $applied = true;
                }

                if (!empty($normalized['filename_core'])) {
                    $query->orWhere('original_filename', 'like', '%' . $normalized['filename_core'] . '%');
                    $applied = true;
                }

                if (!empty($normalized['title'])) {
                    $query->orWhere('current_title', 'like', '%' . $normalized['title'] . '%');
                    $applied = true;
                }

                if (!empty($normalized['city'])) {
                    $query->orWhere('city', 'like', '%' . $normalized['city'] . '%');
                    $applied = true;
                }

                if (!$applied && !empty($normalized['email'])) {
                    $query->orWhereRaw('LOWER(email) = ?', [$normalized['email']]);
                }
            })
            ->latest('uploaded_at')
            ->limit(60)
            ->get();

        $bestMatch = null;

        foreach ($candidates as $candidate) {
            $scored = $this->scoreCandidate($candidate, $normalized);

            if (!$scored) {
                continue;
            }

            if ($bestMatch === null || $scored['score'] > $bestMatch['score']) {
                $bestMatch = $scored;
            }
        }

        if (!$bestMatch || $bestMatch['score'] < 55) {
            return null;
        }

        return $bestMatch;
    }

    private function scoreCandidate(Cv $candidate, array $normalized): ?array
    {
        $candidateName = $this->normalizeText($candidate->candidate_name);
        $candidateEmail = $this->normalizeEmail($candidate->email);
        $candidatePhone = $this->normalizePhone($candidate->phone);
        $candidateTitle = $this->normalizeText($candidate->current_title);
        $candidateCity = $this->normalizeText($candidate->city);
        $candidateFilename = $this->normalizeFilename($candidate->original_filename);
        $candidateFingerprint = $this->buildFingerprint($candidate->encrypted_extracted_text);

        $score = 0;
        $reasons = [];
        $nameSignal = 0;
        $secondarySignals = 0;

        if ($normalized['email'] !== '' && $candidateEmail === $normalized['email']) {
            $score += 60;
            $secondarySignals++;
            $reasons[] = 'email_exact';
        }

        if ($normalized['phone'] !== '' && $candidatePhone === $normalized['phone']) {
            $score += 55;
            $secondarySignals++;
            $reasons[] = 'phone_exact';
        }

        if ($normalized['name'] !== '' && $candidateName !== '') {
            similar_text($normalized['name'], $candidateName, $nameSimilarity);

            if ($normalized['name'] === $candidateName) {
                $score += 35;
                $nameSignal = 3;
                $reasons[] = 'name_exact';
            } elseif ($nameSimilarity >= 90) {
                $score += 28;
                $nameSignal = 2;
                $reasons[] = 'name_similar';
            } elseif ($nameSimilarity >= 80) {
                $score += 18;
                $nameSignal = 1;
                $reasons[] = 'name_close';
            }
        }

        if (
            !empty($normalized['name_parts'])
            && !empty($candidateName)
            && $this->compareNameParts($normalized['name_parts'], $this->nameParts($candidate->candidate_name))
        ) {
            $score += 16;
            $secondarySignals++;
            $reasons[] = 'name_parts_match';
        }

        if (
            !empty($normalized['email_name'])
            && !empty($candidateName)
            && $this->compareNameParts($normalized['email_name_parts'], $this->nameParts($candidate->candidate_name))
        ) {
            $score += 12;
            $secondarySignals++;
            $reasons[] = 'email_name_match';
        }

        if ($normalized['filename_core'] !== '' && $candidateFilename !== '') {
            similar_text($normalized['filename_core'], $candidateFilename, $filenameSimilarity);

            if ($normalized['filename_core'] === $candidateFilename) {
                $score += 18;
                $secondarySignals++;
                $reasons[] = 'filename_exact';
            } elseif ($filenameSimilarity >= 88) {
                $score += 12;
                $secondarySignals++;
                $reasons[] = 'filename_similar';
            }
        }

        if ($normalized['text_fingerprint'] !== '' && $candidateFingerprint !== '') {
            similar_text($normalized['text_fingerprint'], $candidateFingerprint, $textSimilarity);

            if ($normalized['text_fingerprint'] === $candidateFingerprint) {
                $score += 22;
                $secondarySignals++;
                $reasons[] = 'text_fingerprint_exact';
            } elseif ($textSimilarity >= 88) {
                $score += 14;
                $secondarySignals++;
                $reasons[] = 'text_fingerprint_similar';
            }
        }

        if ($normalized['title'] !== '' && $candidateTitle !== '') {
            similar_text($normalized['title'], $candidateTitle, $titleSimilarity);

            if ($normalized['title'] === $candidateTitle) {
                $score += 12;
                $secondarySignals++;
                $reasons[] = 'title_exact';
            } elseif ($titleSimilarity >= 86) {
                $score += 8;
                $secondarySignals++;
                $reasons[] = 'title_similar';
            }
        }

        if ($normalized['city'] !== '' && $candidateCity !== '' && $normalized['city'] === $candidateCity) {
            $score += 6;
            $secondarySignals++;
            $reasons[] = 'city_exact';
        }

        if ($normalized['file_size'] > 0 && (int) $candidate->file_size > 0) {
            $sizeDifference = abs((int) $candidate->file_size - (int) $normalized['file_size']);
            $sizeRatio = $normalized['file_size'] > 0
                ? ($sizeDifference / max(1, (int) $normalized['file_size']))
                : 1;

            if ($sizeDifference <= 2048 || $sizeRatio <= 0.05) {
                $score += 6;
                $secondarySignals++;
                $reasons[] = 'file_size_close';
            }
        }

        $hasStrongIdentity = in_array('email_exact', $reasons, true) || in_array('phone_exact', $reasons, true);

        if (!$hasStrongIdentity && $nameSignal === 0 && $secondarySignals < 2) {
            return null;
        }

        if (!$hasStrongIdentity && $nameSignal === 1 && $secondarySignals < 2) {
            return null;
        }

        if (!$hasStrongIdentity && $nameSignal >= 2 && $secondarySignals < 1 && $score < 60) {
            return null;
        }

        return $this->buildResult($candidate, $score, $reasons);
    }

    private function buildResult(Cv $cv, float $score, array $reasons): array
    {
        return [
            'cv' => $cv,
            'cv_id' => $cv->id,
            'score' => round($score, 2),
            'reason' => implode(', ', array_values(array_unique($reasons))),
        ];
    }

    private function normalizePayload(array $payload): array
    {
        $name = $this->normalizeText($payload['candidate_name'] ?? $payload['full_name'] ?? null);
        $email = $this->normalizeEmail($payload['email'] ?? null);
        $phone = $this->normalizePhone($payload['phone'] ?? null);
        $title = $this->normalizeText($payload['current_title'] ?? $payload['title'] ?? null);
        $city = $this->normalizeText($payload['city'] ?? null);
        $filename = $this->normalizeFilename($payload['original_filename'] ?? null);
        $emailName = $this->emailName($email);

        return [
            'file_hash' => trim((string) ($payload['file_hash'] ?? '')),
            'file_size' => (int) ($payload['file_size'] ?? 0),
            'name' => $name,
            'name_parts' => $this->nameParts($name),
            'name_tokens' => $this->tokenize($name),
            'email' => $email,
            'email_name' => $emailName,
            'email_name_parts' => $this->nameParts($emailName),
            'email_name_tokens' => $this->tokenize($emailName),
            'phone' => $phone,
            'title' => $title,
            'city' => $city,
            'filename_core' => $filename,
            'text_fingerprint' => $this->buildFingerprint($payload['text'] ?? $payload['extracted_text'] ?? null),
        ];
    }

    private function normalizeEmail(?string $email): string
    {
        return mb_strtolower(trim((string) $email));
    }

    private function normalizePhone(?string $phone): string
    {
        $phone = preg_replace('/\D+/', '', (string) $phone);

        if ($phone === '') {
            return '';
        }

        if (str_starts_with($phone, '212') && strlen($phone) >= 11) {
            return '0' . substr($phone, 3);
        }

        if (str_starts_with($phone, '00212') && strlen($phone) >= 13) {
            return '0' . substr($phone, 5);
        }

        return $phone;
    }

    private function normalizeText(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $value = mb_strtolower($value, 'UTF-8');
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        if ($ascii !== false) {
            $value = $ascii;
        }

        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value);

        return trim(preg_replace('/\s+/', ' ', $value));
    }

    private function normalizeFilename(?string $filename): string
    {
        $filename = pathinfo((string) $filename, PATHINFO_FILENAME);
        $filename = $this->normalizeText($filename);
        $filename = preg_replace('/\b(cv|resume|profil|profile|version|final|copy|copie|scan)\b/u', ' ', $filename);

        return trim(preg_replace('/\s+/', ' ', $filename));
    }

    private function buildFingerprint(?string $text): string
    {
        $normalized = $this->normalizeText($text);

        if ($normalized === '') {
            return '';
        }

        $tokens = array_values(array_filter(explode(' ', $normalized), static fn ($token) => mb_strlen($token) >= 4));
        $tokens = array_values(array_unique($tokens));

        return implode(' ', array_slice($tokens, 0, 24));
    }

    private function tokenize(?string $value): array
    {
        $normalized = $this->normalizeText($value);

        if ($normalized === '') {
            return [];
        }

        return array_values(array_filter(explode(' ', $normalized)));
    }

    private function emailName(string $email): string
    {
        if ($email === '' || !str_contains($email, '@')) {
            return '';
        }

        $local = strstr($email, '@', true);
        $local = preg_replace('/[._\-]+/', ' ', $local);

        return $this->normalizeText($local);
    }

    private function nameParts(?string $name): array
    {
        return array_values(array_filter(explode(' ', $this->normalizeText($name))));
    }

    private function compareNameParts(array $left, array $right): bool
    {
        if (count($left) < 2 || count($right) < 2) {
            return false;
        }

        $leftFirst = $left[0];
        $leftLast = $left[count($left) - 1];
        $rightFirst = $right[0];
        $rightLast = $right[count($right) - 1];

        return $leftFirst === $rightFirst && $leftLast === $rightLast;
    }
}
