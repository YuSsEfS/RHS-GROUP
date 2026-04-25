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

    public function scoreRequestMatches(RecruitmentRequest $recruitmentRequest, ?int $folderId = null): int
    {
        $requirements = $recruitmentRequest->ai_normalized_requirements;

        if (!is_array($requirements)) {
            $requirements = [];
        }

        $matches = 0;

        foreach ($this->getTargetCvs($recruitmentRequest->job_offer_id, $folderId) as $cv) {
            $profile = $this->decodeProfile($cv->structured_profile);
            $profile = $this->enrichProfileForScoring($cv, $profile);

            $local = $this->localScorer->score($requirements, $profile);
            $final = $this->formatLocalResult($local);

            CvMatch::updateOrCreate(
                [
                    'recruitment_request_id' => $recruitmentRequest->id,
                    'cv_id' => $cv->id,
                ],
                [
                    'score' => $final['score'] ?? 0,
                    'score_breakdown' => $final['breakdown'] ?? [],
                    'summary' => $final['summary'] ?? '',
                    'selected' => false,
                ]
            );

            $matches++;
        }

        return $matches;
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

        $newBreakdown = array_merge(
            is_array($final['breakdown'] ?? null) ? $final['breakdown'] : [],
            [
                '_meta' => [
                    'local_score' => round((float) ($final['local_score'] ?? $local['score'] ?? 0), 2),
                    'ai_score' => $matchingIa,
                    'final_score' => round((float) ($final['score'] ?? $match->score), 2),
                    'ai_available' => (bool) ($final['ai_available'] ?? false),
                    'last_analysis' => now()->format('Y-m-d H:i:s'),
                ],
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

        if (empty($profile['years_experience'])) {
            $profile['years_experience'] = $this->estimateExperienceFromText($text);
        }

        if (empty($profile['age'])) {
            $profile['age'] = $this->estimateAgeFromText($text);
        }

        return $profile;
    }

    public function formatLocalResult(array $local): array
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
                ],
            ],
            'summary' => (string) ($local['summary'] ?? 'Évaluation locale effectuée.'),
        ];
    }

    private function getTargetCvs($jobOfferId = null, ?int $folderId = null)
    {
        $cvIds = collect();

        if ($folderId && Schema::hasColumn('cvs', 'cv_folder_id')) {
            $cvIds = $cvIds->merge(
                Cv::query()
                    ->where('cv_folder_id', $folderId)
                    ->pluck('id')
            );
        }

        if (!empty($jobOfferId)) {
            $applications = JobApplication::query()
                ->where('job_offer_id', (int) $jobOfferId)
                ->whereNotNull('cv_path')
                ->get();

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
        } elseif (!empty($jobOfferId) || !empty($folderId)) {
            $query->whereRaw('1 = 0');
        }

        return $query->orderByDesc('id')->get();
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
