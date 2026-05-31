<?php

namespace App\Services;

use App\Models\Cv;
use App\Models\CvFolder;
use App\Models\CvMatch;
use App\Models\JobApplication;
use App\Models\RecruitmentRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class RecruitmentScoringService
{
    public function __construct(
        protected LocalCvScoringService $localScorer,
        protected AiRecruitmentAnalysisService $aiAnalysis,
    ) {
    }

    public function scoreRequestMatches(RecruitmentRequest $recruitmentRequest, ?int $folderId = null, array $options = []): int
    {
        $requirements = $recruitmentRequest->ai_normalized_requirements;

        if (!is_array($requirements)) {
            $requirements = [];
        }

        $targetOfferIds = collect($requirements['job_offer_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($targetOfferIds->isEmpty() && !empty($recruitmentRequest->job_offer_id)) {
            $targetOfferIds = collect([(int) $recruitmentRequest->job_offer_id]);
        }

        $targetFolderIds = collect($requirements['cv_folder_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($folderId) {
            $targetFolderIds->push((int) $folderId);
        } elseif ($targetFolderIds->isEmpty() && !empty($recruitmentRequest->cv_folder_id)) {
            $targetFolderIds->push((int) $recruitmentRequest->cv_folder_id);
        }

        $targetQuery = $this->targetCvsQuery($targetOfferIds->all(), $targetFolderIds->unique()->values()->all());
        $total = (clone $targetQuery)->count();
        $matches = 0;
        $processed = 0;

        if (is_callable($options['on_start'] ?? null)) {
            $options['on_start']($total);
        }

        foreach ($targetQuery->orderByDesc('id')->cursor() as $cv) {
            if (is_callable($options['cancelled'] ?? null) && $options['cancelled']()) {
                break;
            }

            if ($this->scoreCvAgainstRequest($recruitmentRequest, $cv, $requirements)) {
                $matches++;
            }

            $processed++;

            if (is_callable($options['on_progress'] ?? null)) {
                $options['on_progress']($processed, $matches, $total);
            }
        }

        return $matches;
    }

    public function scoreCvAgainstRequest(RecruitmentRequest $recruitmentRequest, Cv $cv, ?array $requirements = null): bool
    {
        $requirements ??= $this->decodeProfile($recruitmentRequest->ai_normalized_requirements);

        if (empty($requirements) || empty($cv->structured_profile)) {
            return false;
        }

        if (!$this->cvIsEligibleForRequest($recruitmentRequest, $cv, $requirements)) {
            return false;
        }

        $profile = $this->decodeProfile($cv->structured_profile);
        $profile = $this->enrichProfileForScoring($cv, $profile);

        $criteriaSignature = $this->criteriaSignature($requirements);
        $profileSignature = $this->profileSignature($profile);

        $match = CvMatch::firstOrNew([
            'recruitment_request_id' => $recruitmentRequest->id,
            'cv_id' => $cv->id,
        ]);

        if ($match->exists) {
            $breakdown = is_array($match->score_breakdown ?? null)
                ? $match->score_breakdown
                : (json_decode($match->score_breakdown ?? '[]', true) ?: []);
            $meta = is_array($breakdown['_meta'] ?? null) ? $breakdown['_meta'] : [];

            if (
                ($meta['criteria_signature'] ?? null) === $criteriaSignature
                && ($meta['profile_signature'] ?? null) === $profileSignature
            ) {
                return true;
            }
        }

        $local = $this->localScorer->score($requirements, $profile);
        $local['explanations'] = $this->attachCriterionEvidence($local['explanations'] ?? [], $profile);
        $final = $this->formatLocalResult($local, $criteriaSignature, $profileSignature);

        $match->fill([
            'score' => $final['score'] ?? 0,
            'score_breakdown' => $final['breakdown'] ?? [],
            'summary' => $final['summary'] ?? '',
        ]);

        if (!$match->exists) {
            $match->selected = false;
        }

        $match->save();

        return true;
    }

    public function analyzeMatchWithAi(CvMatch $match): array
    {
        $match->loadMissing(['recruitmentRequest', 'cv']);

        $recruitmentRequest = $match->recruitmentRequest;
        $cv = $match->cv;

        if (!$recruitmentRequest || !$cv) {
            return ['success' => false, 'message' => 'Match introuvable.'];
        }

        $requirements = $this->decodeProfile($recruitmentRequest->ai_normalized_requirements);

        if (empty($requirements)) {
            return ['success' => false, 'message' => 'Les exigences du poste ne sont pas disponibles.'];
        }

        $profile = $this->decodeProfile($cv->structured_profile);

        if (empty($profile)) {
            return ['success' => false, 'message' => 'Le profil structuré du CV est introuvable.'];
        }

        $profile = $this->enrichProfileForScoring($cv, $profile);
        $local = $this->localScorer->score($requirements, $profile);

        $final = $this->aiAnalysis->analyze(
            $requirements,
            $profile,
            (float) ($local['score'] ?? 0),
            (array) ($local['breakdown'] ?? []),
            (string) ($local['summary'] ?? '')
        );

        $matchingIa = isset($final['ai_score']) && $final['ai_score'] !== null
            ? round((float) $final['ai_score'], 2)
            : null;

        $existingBreakdown = is_array($match->score_breakdown ?? null)
            ? $match->score_breakdown
            : (json_decode($match->score_breakdown ?? '[]', true) ?: []);
        $existingMeta = is_array($existingBreakdown['_meta'] ?? null) ? $existingBreakdown['_meta'] : [];

        $newBreakdown = array_merge(
            is_array($final['breakdown'] ?? null) ? $final['breakdown'] : [],
            [
                '_meta' => array_merge($existingMeta, [
                    'local_score' => round((float) ($final['local_score'] ?? $local['score'] ?? 0), 2),
                    'ai_score' => $matchingIa,
                    'final_score' => round((float) ($final['score'] ?? $match->score), 2),
                    'ai_available' => (bool) ($final['ai_available'] ?? false),
                    'last_analysis' => now()->format('Y-m-d H:i:s'),
                ]),
            ]
        );

        $match->update([
            'score' => $final['score'] ?? $match->score,
            'score_breakdown' => $newBreakdown,
            'summary' => $final['summary'] ?? $match->summary,
        ]);

        return [
            'success' => true,
            'ai_available' => (bool) ($final['ai_available'] ?? false),
            'message' => ($final['ai_available'] ?? false)
                ? 'Analyse IA effectuée avec succès. Le score de matching a été mis à jour.'
                : 'OpenAI est temporairement limité. Un matching avancé estimé localement a été appliqué avec succès.',
        ];
    }

    public function enrichProfileForScoring(Cv $cv, array $profile): array
    {
        if (empty($profile['full_name']) && !empty($cv->candidate_name)) {
            $profile['full_name'] = $cv->candidate_name;
        }

        if (empty($profile['email']) && !empty($cv->email)) {
            $profile['email'] = $cv->email;
        }

        if (empty($profile['phone']) && !empty($cv->phone)) {
            $profile['phone'] = $cv->phone;
        }

        if (empty($profile['city']) && !empty($cv->city)) {
            $profile['city'] = $cv->city;
        }

        if (empty($profile['title']) && !empty($cv->current_title)) {
            $profile['title'] = $cv->current_title;
        }

        $text = (string) ($cv->encrypted_extracted_text ?? data_get($profile, 'summary') ?? '');

        if ($text !== '') {
            $profile['raw_text'] = $text;
        }

        if (empty($profile['years_experience'])) {
            $profile['years_experience'] = $this->estimateExperienceFromText($text);
        }

        if (empty($profile['age'])) {
            $profile['age'] = $this->estimateAgeFromText($text);
        }

        return $profile;
    }

    public function formatLocalResult(array $local, ?string $criteriaSignature = null, ?string $profileSignature = null): array
    {
        return [
            'score' => (float) ($local['score'] ?? 0),
            'breakdown' => [
                'title_fit' => (float) (($local['breakdown']['title'] ?? 0)),
                'education_fit' => (float) (($local['breakdown']['education'] ?? 0)),
                'experience_fit' => (float) (($local['breakdown']['experience'] ?? 0)),
                'skills_fit' => round(
                    (float) (($local['breakdown']['must_have_skills'] ?? 0)) +
                    (float) (($local['breakdown']['nice_to_have_skills'] ?? 0)),
                    2
                ),
                'language_fit' => (float) (($local['breakdown']['languages'] ?? 0)),
                'location_fit' => (float) (($local['breakdown']['location'] ?? 0)),
                'availability_fit' => (float) (($local['breakdown']['availability'] ?? 0)),
                'overall_consistency' => round(
                    (float) (($local['breakdown']['soft_skills'] ?? 0)) +
                    (float) (($local['breakdown']['consistency_bonus'] ?? 0)),
                    2
                ),
                '_meta' => [
                    'local_score' => round((float) ($local['score'] ?? 0), 2),
                    'ai_score' => null,
                    'final_score' => round((float) ($local['score'] ?? 0), 2),
                    'ai_available' => false,
                    'last_analysis' => null,
                    'criteria_signature' => $criteriaSignature,
                    'profile_signature' => $profileSignature,
                    'explanations' => is_array($local['explanations'] ?? null) ? $local['explanations'] : [],
                ],
            ],
            'summary' => (string) ($local['summary'] ?? 'Évaluation locale effectuée.'),
        ];
    }

    private function getTargetCvs($jobOfferIds = null, $folderIds = null)
    {
        return $this->targetCvsQuery($jobOfferIds, $folderIds)
            ->orderByDesc('id')
            ->cursor();
    }

    private function targetCvsQuery($jobOfferIds = null, $folderIds = null)
    {
        $cvIds = collect();
        $jobOfferIds = collect(is_array($jobOfferIds) ? $jobOfferIds : [$jobOfferIds])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $folderIds = collect(is_array($folderIds) ? $folderIds : [$folderIds])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($folderIds->isNotEmpty() && Schema::hasColumn('cvs', 'cv_folder_id')) {
            $cvIds = $cvIds->merge(
                Cv::query()
                    ->whereIn('cv_folder_id', $folderIds->all())
                    ->pluck('id')
            );
        }

        if ($jobOfferIds->isNotEmpty()) {
            $applications = JobApplication::query()
                ->whereIn('job_offer_id', $jobOfferIds->all())
                ->whereNotNull('cv_path')
                ->cursor();

            foreach ($applications as $application) {
                $relativePath = ltrim((string) $application->cv_path, '/');

                if ($relativePath === '' || !Storage::disk('public')->exists($relativePath)) {
                    continue;
                }

                $binary = Storage::disk('public')->get($relativePath);
                $hash = hash('sha256', $binary);

                $existingCv = Cv::query()
                    ->where('file_hash', $hash)
                    ->first();

                if ($existingCv) {
                    $cvIds->push($existingCv->id);
                }
            }
        }

        $query = Cv::query()->whereNotNull('structured_profile');

        if ($cvIds->isNotEmpty()) {
            $query->whereIn('id', $cvIds->unique()->values()->all());
        } elseif ($jobOfferIds->isNotEmpty() || $folderIds->isNotEmpty()) {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    private function criteriaSignature(array $requirements): string
    {
        ksort($requirements);

        return hash('sha256', json_encode($requirements, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function profileSignature(array $profile): string
    {
        foreach (['raw_text'] as $heavyKey) {
            unset($profile[$heavyKey]);
        }

        ksort($profile);

        return hash('sha256', json_encode($profile, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function attachCriterionEvidence(array $explanations, array $profile): array
    {
        $evidence = [
            'title' => $this->normalizeWhitespace((string) data_get($profile, 'title')),
            'education' => $this->normalizeWhitespace((string) data_get($profile, 'education')),
            'experience' => data_get($profile, 'years_experience') !== null
                ? $this->normalizeWhitespace((string) data_get($profile, 'years_experience')) . ' annees detectees'
                : '',
            'skills' => implode(', ', array_slice((array) data_get($profile, 'skills', []), 0, 10)),
            'languages' => implode(', ', array_slice((array) data_get($profile, 'languages', []), 0, 6)),
            'location' => $this->normalizeWhitespace((string) (data_get($profile, 'city') ?: data_get($profile, 'location'))),
            'availability' => $this->normalizeWhitespace((string) data_get($profile, 'availability')),
        ];

        foreach ($evidence as $key => $value) {
            if ($value === '') {
                continue;
            }

            $prefix = isset($explanations[$key]) && $explanations[$key] !== ''
                ? rtrim((string) $explanations[$key])
                : 'Score base sur les informations structurees du CV.';

            $explanations[$key] = $prefix . ' Evidence CV : ' . $value . '.';
        }

        return $explanations;
    }

    private function cvIsEligibleForRequest(RecruitmentRequest $request, Cv $cv, array $requirements): bool
    {
        $folderIds = collect($requirements['cv_folder_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($folderIds->isEmpty() && !empty($requirements['cv_folder_id'])) {
            $folderIds->push((int) $requirements['cv_folder_id']);
        }

        if ($folderIds->isEmpty() && !empty($request->cv_folder_id)) {
            $folderIds->push((int) $request->cv_folder_id);
        }

        if ($folderIds->isNotEmpty() && (int) ($cv->cv_folder_id ?? 0) > 0) {
            return $folderIds->contains((int) $cv->cv_folder_id);
        }

        return true;
    }

    private function estimateExperienceFromText(?string $text): ?float
    {
        $text = $this->normalizeWhitespace((string) $text);

        if ($text === '') {
            return null;
        }

        $norm = mb_strtolower($text, 'UTF-8');
        $best = null;

        if (preg_match_all('/(\d{4})\s*[-–—]\s*(\d{4}|present|presentement|présent|actuel|aujourd)/iu', $norm, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $start = (int) $match[1];
                $end = preg_match('/^\d{4}$/', $match[2]) ? (int) $match[2] : (int) date('Y');

                if ($start >= 1970 && $start <= (int) date('Y') && $end >= $start) {
                    $best = max($best ?? 0, min(40, $end - $start));
                }
            }
        }

        if (preg_match_all('/(\d+(?:[.,]\d+)?)\s*(?:ans|annees|années|year|years)\s+(?:d[’\'e ]experience|experience|expérience)/iu', $norm, $matches)) {
            foreach ($matches[1] as $value) {
                $best = max($best ?? 0, (float) str_replace(',', '.', $value));
            }
        }

        return $best;
    }

    private function estimateAgeFromText(?string $text): ?int
    {
        $text = $this->normalizeWhitespace((string) $text);

        if ($text === '') {
            return null;
        }

        if (preg_match('/\b(\d{1,2})\s*(?:ans|years old|year old)\b/iu', $text, $m)) {
            $age = (int) $m[1];

            if ($age >= 16 && $age <= 70) {
                return $age;
            }
        }

        if (preg_match('/(?:né|nee|née|naissance|born).*?(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})/iu', $text, $m)) {
            $year = (int) $m[3];

            if ($year >= 1950 && $year <= (int) date('Y')) {
                return (int) date('Y') - $year;
            }
        }

        if (preg_match('/\b(19[5-9]\d|20[0-1]\d)\b/u', $text, $m)) {
            $year = (int) $m[1];
            $age = (int) date('Y') - $year;

            if ($age >= 16 && $age <= 70) {
                return $age;
            }
        }

        return null;
    }

    private function normalizeWhitespace(?string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) $value));
    }

    private function decodeProfile($payload): array
    {
        if (is_array($payload)) {
            return $payload;
        }

        if (is_string($payload)) {
            $decoded = json_decode($payload, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
