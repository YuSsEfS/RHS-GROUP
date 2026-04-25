<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AiFinalCvScoringService
{
    protected $client = null;
    protected string $model;
    protected bool $configured = false;

    public function __construct()
    {
        $apiKey = config('services.openai.api_key');
        $this->model = (string) config('services.openai.model', env('OPENAI_MODEL', 'gpt-4o-mini'));

        if (!$apiKey) {
            Log::info('OpenAI disabled for final CV scoring: missing API key.', [
                'model' => $this->model,
            ]);

            return;
        }

        $this->client = \OpenAI::client($apiKey);
        $this->configured = true;
    }

    public function score(
        array $requirements,
        array $profile,
        float $localScore = 0,
        array $localBreakdown = [],
        string $localSummary = ''
    ): array {
        if (!$this->configured) {
            $estimatedAiScore = $this->estimateAiLikeScore($localScore, $localBreakdown);

            return [
                'score' => round(($localScore * 0.7) + ($estimatedAiScore * 0.3), 2),
                'local_score' => round($localScore, 2),
                'ai_score' => round($estimatedAiScore, 2),
                'breakdown' => $this->normalizeBreakdown($this->mapLocalBreakdown($localBreakdown)),
                'summary' => $this->fallbackSummary($localSummary, $localScore, $estimatedAiScore),
                'ai_available' => false,
                'failure_reason' => 'openai_not_configured',
            ];
        }

        $attempts = 4;
        $lastException = null;

        for ($try = 1; $try <= $attempts; $try++) {
            try {
                $messages = [
                    [
                        'role' => 'system',
                        'content' => <<<TXT
Tu es un evaluateur RH expert.

Analyse l adequation entre une demande de recrutement et un candidat.

Retourne uniquement un JSON valide avec cette structure exacte :
{
  "score": 0,
  "breakdown": {
    "title_fit": 0,
    "education_fit": 0,
    "experience_fit": 0,
    "skills_fit": 0,
    "language_fit": 0,
    "location_fit": 0,
    "availability_fit": 0,
    "overall_consistency": 0
  },
  "summary": {
    "resume_profil": "",
    "points_forts": [],
    "points_vigilance": [],
    "adequation_poste": "",
    "recommandation_finale": ""
  }
}

Regles :
- score entre 0 et 100
- style professionnel RH
- formulations courtes et concretes
- pas de marketing ni de formule generique
- pas de texte hors JSON
TXT
                    ],
                    [
                        'role' => 'user',
                        'content' => json_encode([
                            'requirements' => $this->compactRequirements($requirements),
                            'candidate_profile' => $this->compactProfile($profile),
                            'local_pre_score' => round($localScore, 2),
                            'local_breakdown' => $localBreakdown,
                            'local_summary' => $localSummary,
                        ], JSON_UNESCAPED_UNICODE),
                    ],
                ];

                $response = $this->client->chat()->create([
                    'model' => $this->model,
                    'temperature' => 0.1,
                    'messages' => $messages,
                ]);

                $content = trim($response->choices[0]->message->content ?? '');
                $data = $this->extractJson($content);

                if (!is_array($data)) {
                    throw new \RuntimeException('Reponse IA invalide: ' . $content);
                }

                $aiScore = max(0, min(100, (float) ($data['score'] ?? $localScore)));
                $finalScore = round(($localScore * 0.55) + ($aiScore * 0.45), 2);

                return [
                    'score' => $finalScore,
                    'local_score' => round($localScore, 2),
                    'ai_score' => round($aiScore, 2),
                    'breakdown' => $this->normalizeBreakdown(
                        is_array($data['breakdown'] ?? null)
                            ? $data['breakdown']
                            : $this->mapLocalBreakdown($localBreakdown)
                    ),
                    'summary' => $this->formatSummarySections($data['summary'] ?? null, $localSummary),
                    'ai_available' => true,
                    'failure_reason' => null,
                ];
            } catch (\Throwable $e) {
                $lastException = $e;

                Log::warning('AiFinalCvScoringService retry', [
                    'try' => $try,
                    'message' => $e->getMessage(),
                    'model' => $this->model,
                ]);

                $isRateLimit = str_contains(mb_strtolower($e->getMessage()), 'rate limit')
                    || str_contains(mb_strtolower($e->getMessage()), '429')
                    || str_contains(mb_strtolower($e->getMessage()), 'too many requests');

                if ($isRateLimit && $try < $attempts) {
                    sleep(2 ** $try);
                    continue;
                }

                break;
            }
        }

        Log::error('AiFinalCvScoringService failed', [
            'message' => $lastException?->getMessage(),
            'model' => $this->model,
        ]);

        $estimatedAiScore = $this->estimateAiLikeScore($localScore, $localBreakdown);

        return [
            'score' => round(($localScore * 0.7) + ($estimatedAiScore * 0.3), 2),
            'local_score' => round($localScore, 2),
            'ai_score' => round($estimatedAiScore, 2),
            'breakdown' => $this->normalizeBreakdown($this->mapLocalBreakdown($localBreakdown)),
            'summary' => $this->fallbackSummary($localSummary, $localScore, $estimatedAiScore),
            'ai_available' => false,
            'failure_reason' => $lastException?->getMessage(),
        ];
    }

    private function estimateAiLikeScore(float $localScore, array $localBreakdown): float
    {
        $title = (float) ($localBreakdown['title'] ?? 0);
        $experience = (float) ($localBreakdown['experience'] ?? 0);
        $must = (float) ($localBreakdown['must_have_skills'] ?? 0);
        $nice = (float) ($localBreakdown['nice_to_have_skills'] ?? 0);
        $education = (float) ($localBreakdown['education'] ?? 0);
        $languages = (float) ($localBreakdown['languages'] ?? 0);

        $skillsTotal = $must + $nice;

        $bonus = 0;
        if ($title >= 14) {
            $bonus += 4;
        }
        if ($experience >= 12) {
            $bonus += 4;
        }
        if ($skillsTotal >= 16) {
            $bonus += 6;
        }
        if ($education >= 6) {
            $bonus += 2;
        }
        if ($languages >= 4) {
            $bonus += 2;
        }

        return max(0, min(100, round($localScore + $bonus, 2)));
    }

    private function compactRequirements(array $requirements): array
    {
        return [
            'role' => $requirements['role'] ?? '',
            'must_have_skills' => array_values(array_slice((array) ($requirements['must_have_skills'] ?? []), 0, 8)),
            'nice_to_have_skills' => array_values(array_slice((array) ($requirements['nice_to_have_skills'] ?? []), 0, 5)),
            'education' => $requirements['education'] ?? '',
            'min_experience_years' => $requirements['min_experience_years'] ?? '',
            'languages' => array_values(array_slice((array) ($requirements['languages'] ?? []), 0, 4)),
            'location' => $requirements['location'] ?? '',
            'availability' => $requirements['availability'] ?? '',
            'soft_skills' => array_values(array_slice((array) ($requirements['soft_skills'] ?? []), 0, 5)),
            'mission_keywords' => array_values(array_slice((array) ($requirements['mission_keywords'] ?? []), 0, 6)),
        ];
    }

    private function compactProfile(array $profile): array
    {
        return [
            'full_name' => $profile['full_name'] ?? '',
            'title' => $profile['title'] ?? '',
            'headline' => $profile['headline'] ?? '',
            'desired_position' => $profile['desired_position'] ?? '',
            'years_experience' => $profile['years_experience'] ?? '',
            'education' => $profile['education'] ?? [],
            'languages' => array_values(array_slice((array) ($profile['languages'] ?? []), 0, 5)),
            'technical_skills' => array_values(array_slice((array) ($profile['technical_skills'] ?? []), 0, 10)),
            'soft_skills' => array_values(array_slice((array) ($profile['soft_skills'] ?? []), 0, 6)),
            'certifications' => array_values(array_slice((array) ($profile['certifications'] ?? []), 0, 5)),
            'location' => $profile['location'] ?? '',
            'city' => $profile['city'] ?? '',
            'availability' => $profile['availability'] ?? '',
            'summary' => mb_substr((string) ($profile['summary'] ?? ''), 0, 450),
        ];
    }

    private function extractJson(string $content): ?array
    {
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function mapLocalBreakdown(array $local): array
    {
        return [
            'title_fit' => (float) ($local['title'] ?? 0),
            'education_fit' => (float) ($local['education'] ?? 0),
            'experience_fit' => (float) ($local['experience'] ?? 0),
            'skills_fit' => round(
                (float) ($local['must_have_skills'] ?? 0) +
                (float) ($local['nice_to_have_skills'] ?? 0),
                2
            ),
            'language_fit' => (float) ($local['languages'] ?? 0),
            'location_fit' => (float) ($local['location'] ?? 0),
            'availability_fit' => (float) ($local['availability'] ?? 0),
            'overall_consistency' => round(
                (float) ($local['soft_skills'] ?? 0) +
                (float) ($local['consistency_bonus'] ?? 0),
                2
            ),
        ];
    }

    private function normalizeBreakdown(array $breakdown): array
    {
        $keys = [
            'title_fit',
            'education_fit',
            'experience_fit',
            'skills_fit',
            'language_fit',
            'location_fit',
            'availability_fit',
            'overall_consistency',
        ];

        $out = [];

        foreach ($keys as $key) {
            $out[$key] = round(max(0, (float) ($breakdown[$key] ?? 0)), 2);
        }

        return $out;
    }

    private function fallbackSummary(string $localSummary, float $localScore, float $estimatedAiScore): string
    {
        $adequation = round(($localScore + $estimatedAiScore) / 2, 1);

        return trim(implode("\n", [
            'Resume du profil: analyse locale disponible en attendant l analyse IA.',
            'Points forts: score local ' . round($localScore, 1) . '/100.',
            'Points de vigilance: validation humaine recommandee pour confirmer l adequation detaillee.',
            'Adequation avec le poste: estimation actuelle ' . $adequation . '/100.',
            'Recommandation finale: poursuivre l evaluation RH si le contexte metier reste pertinent.',
        ]));
    }

    private function formatSummarySections($summary, string $fallback): string
    {
        if (is_string($summary) && trim($summary) !== '') {
            return trim($summary);
        }

        if (!is_array($summary)) {
            return $fallback !== '' ? $fallback : 'Analyse IA realisee.';
        }

        $lines = [];

        $resume = trim((string) ($summary['resume_profil'] ?? ''));
        $strengths = array_filter((array) ($summary['points_forts'] ?? []));
        $warnings = array_filter((array) ($summary['points_vigilance'] ?? []));
        $adequation = trim((string) ($summary['adequation_poste'] ?? ''));
        $recommendation = trim((string) ($summary['recommandation_finale'] ?? ''));

        if ($resume !== '') {
            $lines[] = 'Resume du profil: ' . $resume;
        }

        if (!empty($strengths)) {
            $lines[] = 'Points forts: ' . implode(', ', $strengths) . '.';
        }

        if (!empty($warnings)) {
            $lines[] = 'Points de vigilance: ' . implode(', ', $warnings) . '.';
        }

        if ($adequation !== '') {
            $lines[] = 'Adequation avec le poste: ' . $adequation;
        }

        if ($recommendation !== '') {
            $lines[] = 'Recommandation finale: ' . $recommendation;
        }

        return empty($lines)
            ? ($fallback !== '' ? $fallback : 'Analyse IA realisee.')
            : implode("\n", $lines);
    }
}
