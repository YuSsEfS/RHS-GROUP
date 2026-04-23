<?php

namespace App\Services;

class LocalCvScoringService
{
    protected array $synonyms;
    protected array $titleFamilies;
    protected array $roleSiblings;

    public function __construct()
    {
        $this->synonyms = config('cv_synonyms.synonyms', []);
        $this->titleFamilies = config('cv_synonyms.title_families', []);
        $this->roleSiblings = config('cv_synonyms.role_siblings', []);
    }

    public function score(array $requirements, array $profile): array
    {
        $breakdown = [
            'title' => 0,
            'education' => 0,
            'experience' => 0,
            'must_have_skills' => 0,
            'nice_to_have_skills' => 0,
            'languages' => 0,
            'location' => 0,
            'availability' => 0,
            'soft_skills' => 0,
            'consistency_bonus' => 0,
        ];

        $weights = [
            'title' => 26,
            'education' => 10,
            'experience' => 18,
            'must_have_skills' => 24,
            'nice_to_have_skills' => 5,
            'languages' => 5,
            'location' => 3,
            'availability' => 2,
            'soft_skills' => 3,
            'consistency_bonus' => 2,
        ];

        $activeWeights = [];
        $summaryParts = [];

        $profileTitle = $this->normalizeText(
            $profile['title']
            ?? $profile['headline']
            ?? $profile['desired_position']
            ?? ''
        );

        $profileEducation = $this->normalizeText(
            is_array($profile['education'] ?? null)
                ? implode(' ', $profile['education'])
                : ($profile['education'] ?? '')
        );

        $profileSkills = $this->normalizeText(
            is_array($profile['technical_skills'] ?? null)
                ? implode(' ', $profile['technical_skills'])
                : ($profile['technical_skills'] ?? '')
        );

        $profileSoftSkills = $this->normalizeText(
            is_array($profile['soft_skills'] ?? null)
                ? implode(' ', $profile['soft_skills'])
                : ($profile['soft_skills'] ?? '')
        );

        $profileLanguages = $this->normalizeText(
            is_array($profile['languages'] ?? null)
                ? implode(' ', $profile['languages'])
                : ($profile['languages'] ?? '')
        );

        $profileSummary = $this->normalizeText((string) ($profile['summary'] ?? ''));
        $profileLocation = $this->normalizeText(($profile['location'] ?? '') . ' ' . ($profile['city'] ?? ''));
        $profileAvailability = $this->normalizeText((string) ($profile['availability'] ?? ''));
        $profileYearsText = $this->normalizeText((string) ($profile['years_experience'] ?? '') . ' ' . ($profile['summary'] ?? ''));

        $profilePool = $this->normalizeText(implode(' ', array_filter([
            $profileTitle,
            $profileEducation,
            $profileSkills,
            $profileSoftSkills,
            $profileLanguages,
            $profileSummary,
            $profileLocation,
            $profileAvailability,
            $profileYearsText,
            is_array($profile['certifications'] ?? null) ? implode(' ', $profile['certifications']) : ($profile['certifications'] ?? ''),
            is_array($profile['industries'] ?? null) ? implode(' ', $profile['industries']) : ($profile['industries'] ?? ''),
        ])));

        // TITLE
        $reqRole = $this->normalizeText((string) ($requirements['role'] ?? ''));
        if ($reqRole !== '') {
            $activeWeights['title'] = $weights['title'];

            $ratio = $this->scoreTitleFit($reqRole, $profileTitle, $profilePool);
            $breakdown['title'] = round($ratio * $weights['title'], 2);

            if ($ratio >= 0.84) {
                $summaryParts[] = 'poste très bien aligné';
            } elseif ($ratio >= 0.65) {
                $summaryParts[] = 'poste bien aligné';
            } elseif ($ratio >= 0.45) {
                $summaryParts[] = 'poste partiellement aligné';
            }
        }

        // EDUCATION
        $reqEducation = $this->normalizeText((string) ($requirements['education'] ?? ''));
        if ($reqEducation !== '') {
            $activeWeights['education'] = $weights['education'];

            $ratio = $this->scoreEducationFit($reqEducation, $profileEducation, $profilePool);
            $breakdown['education'] = round($ratio * $weights['education'], 2);

            if ($ratio >= 0.8) {
                $summaryParts[] = 'formation adaptée';
            }
        }

        // EXPERIENCE
        $reqExp = $this->extractYears((string) ($requirements['min_experience_years'] ?? ''));
        $cvExp = $this->extractYears($profileYearsText);

        if ($reqExp > 0) {
            $activeWeights['experience'] = $weights['experience'];

            $ratio = $this->scoreExperienceFit($reqExp, $cvExp);
            $breakdown['experience'] = round($ratio * $weights['experience'], 2);

            if ($ratio >= 0.95) {
                $summaryParts[] = 'expérience conforme';
            } elseif ($ratio >= 0.78) {
                $summaryParts[] = 'expérience proche du besoin';
            }
        }

        // SKILLS
        $must = $this->uniqueKeywords($requirements['must_have_skills'] ?? []);
        $nice = $this->uniqueKeywords(array_merge(
            $this->toKeywordArray($requirements['nice_to_have_skills'] ?? []),
            $this->toKeywordArray($requirements['mission_keywords'] ?? [])
        ));
        $langs = $this->uniqueKeywords($requirements['languages'] ?? []);
        $soft = $this->uniqueKeywords($requirements['soft_skills'] ?? []);

        if (!empty($must)) {
            $activeWeights['must_have_skills'] = $weights['must_have_skills'];

            $mustRatio = $this->scoreKeywordsStrict($must, $profilePool);
            $breakdown['must_have_skills'] = round($mustRatio * $weights['must_have_skills'], 2);

            if ($mustRatio >= 0.8) {
                $summaryParts[] = 'compétences clés bien couvertes';
            } elseif ($mustRatio >= 0.55) {
                $summaryParts[] = 'compétences clés partiellement couvertes';
            }
        } else {
            $mustRatio = 0;
        }

        if (!empty($nice)) {
            $activeWeights['nice_to_have_skills'] = $weights['nice_to_have_skills'];

            $niceRatio = $this->scoreKeywordsFlexible($nice, $profilePool);
            $breakdown['nice_to_have_skills'] = round($niceRatio * $weights['nice_to_have_skills'], 2);
        }

        // LANGUAGES
        if (!empty($langs)) {
            $activeWeights['languages'] = $weights['languages'];

            $langRatio = $this->scoreKeywordsStrict($langs, $profileLanguages . ' ' . $profilePool);
            $breakdown['languages'] = round($langRatio * $weights['languages'], 2);
        }

        // SOFT SKILLS
        if (!empty($soft)) {
            $activeWeights['soft_skills'] = $weights['soft_skills'];

            $softRatio = $this->scoreKeywordsFlexible($soft, $profileSoftSkills . ' ' . $profilePool);
            $breakdown['soft_skills'] = round($softRatio * $weights['soft_skills'], 2);
        }

        // LOCATION
        $reqLocation = $this->normalizeText((string) ($requirements['location'] ?? ''));
        if ($reqLocation !== '') {
            $activeWeights['location'] = $weights['location'];

            $ratio = $this->scoreLocationFit($reqLocation, $profileLocation, $profilePool);
            $breakdown['location'] = round($ratio * $weights['location'], 2);
        }

        // AVAILABILITY
        $reqAvailability = $this->normalizeText((string) ($requirements['availability'] ?? ''));
        if ($reqAvailability !== '') {
            $activeWeights['availability'] = $weights['availability'];

            $ratio = $this->scoreAvailabilityFit($reqAvailability, $profileAvailability, $profilePool);
            $breakdown['availability'] = round($ratio * $weights['availability'], 2);
        }

        // BONUS
        $activeWeights['consistency_bonus'] = $weights['consistency_bonus'];
        $bonus = 0;

        if (($breakdown['title'] ?? 0) >= ($weights['title'] * 0.62)) {
            $bonus += 0.9;
        }
        if (($breakdown['experience'] ?? 0) >= ($weights['experience'] * 0.62)) {
            $bonus += 0.5;
        }
        if (($breakdown['must_have_skills'] ?? 0) >= ($weights['must_have_skills'] * 0.62)) {
            $bonus += 0.6;
        }

        $breakdown['consistency_bonus'] = round(min($weights['consistency_bonus'], $bonus), 2);

        $rawScore = array_sum($breakdown);
        $maxScore = max(1, array_sum($activeWeights));
        $score = round(min(100, ($rawScore / $maxScore) * 100), 2);

        $summary = !empty($summaryParts)
            ? ucfirst(implode(', ', array_slice(array_unique($summaryParts), 0, 3))) . '.'
            : 'Évaluation locale fondée sur le poste, les compétences, l’expérience et la cohérence globale.';

        return [
            'score' => $score,
            'breakdown' => $breakdown,
            'summary' => $summary,
            'meta' => [
                'raw_score' => round($rawScore, 2),
                'max_score' => round($maxScore, 2),
            ],
        ];
    }

    private function normalizeText(string $text): string
    {
        $text = mb_strtolower($text);
        $text = str_replace(['é', 'è', 'ê', 'ë'], 'e', $text);
        $text = str_replace(['à', 'â'], 'a', $text);
        $text = str_replace(['î', 'ï'], 'i', $text);
        $text = str_replace(['ô', 'ö'], 'o', $text);
        $text = str_replace(['ù', 'û', 'ü'], 'u', $text);
        $text = str_replace(['ç'], 'c', $text);
        $text = preg_replace('/[^\p{L}\p{N}\s\+\#\.\-\/]+/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim($text);
    }

    private function toKeywordArray($value): array
    {
        $items = is_array($value) ? $value : preg_split('/[,;|\n]+/u', (string) $value);
        $out = [];

        foreach ($items as $item) {
            $item = $this->normalizeText((string) $item);
            if ($item !== '') {
                $out[] = $item;
            }
        }

        return $out;
    }

    private function uniqueKeywords($value): array
    {
        return array_values(array_unique(array_filter($this->toKeywordArray($value))));
    }

    private function expand(string $keyword): array
    {
        $keyword = $this->normalizeText($keyword);
        $variants = [$keyword];

        if (isset($this->synonyms[$keyword])) {
            $variants = array_merge($variants, $this->synonyms[$keyword]);
        }

        return array_values(array_unique(array_map(fn ($v) => $this->normalizeText($v), $variants)));
    }

    private function scoreKeywordsStrict(array $required, string $pool): float
    {
        if (empty($required)) {
            return 0;
        }

        $score = 0;

        foreach ($required as $keyword) {
            $variants = $this->expand($keyword);
            $matched = false;
            $partial = false;

            foreach ($variants as $variant) {
                if ($this->containsPhrase($pool, $variant)) {
                    $matched = true;
                    break;
                }

                if ($this->tokenOverlap($variant, $pool) >= 0.70) {
                    $partial = true;
                }
            }

            if ($matched) {
                $score += 1;
            } elseif ($partial) {
                $score += 0.45;
            }
        }

        return min(1, $score / count($required));
    }

    private function scoreKeywordsFlexible(array $required, string $pool): float
    {
        if (empty($required)) {
            return 0;
        }

        $score = 0;

        foreach ($required as $keyword) {
            $variants = $this->expand($keyword);
            $matched = false;
            $partial = false;

            foreach ($variants as $variant) {
                if ($this->containsPhrase($pool, $variant)) {
                    $matched = true;
                    break;
                }

                if ($this->tokenOverlap($variant, $pool) >= 0.50) {
                    $partial = true;
                }
            }

            if ($matched) {
                $score += 1;
            } elseif ($partial) {
                $score += 0.65;
            }
        }

        return min(1, $score / count($required));
    }

    private function scoreTitleFit(string $required, string $candidate, string $pool): float
    {
        $required = $this->normalizeText($required);
        $candidate = $this->normalizeText($candidate);

        if ($required === '') {
            return 0;
        }

        $direct = $this->phraseSimilarity($required, $candidate, $pool);
        $familyBoost = $this->sameTitleFamily($required, $candidate, $pool) ? 0.18 : 0;
        $siblingPenalty = $this->isSiblingRole($required, $candidate, $pool) ? 0.22 : 0;

        return max(0, min(1, $direct + $familyBoost - $siblingPenalty));
    }

    private function scoreEducationFit(string $required, string $candidate, string $pool): float
    {
        $reqRank = $this->educationRank($required);
        $candRank = $this->educationRank($candidate . ' ' . $pool);

        if ($reqRank > 0 && $candRank > 0) {
            if ($candRank >= $reqRank) {
                return 1;
            }

            if ($candRank === $reqRank - 1) {
                return 0.72;
            }

            return 0.35;
        }

        return $this->phraseSimilarity($required, $candidate, $pool);
    }

    private function scoreExperienceFit(float $requiredYears, float $candidateYears): float
    {
        if ($requiredYears <= 0) {
            return 0;
        }

        if ($candidateYears <= 0) {
            return 0.12;
        }

        $ratio = $candidateYears / $requiredYears;

        if ($ratio >= 1.0) {
            return 1.0;
        }
        if ($ratio >= 0.85) {
            return 0.9;
        }
        if ($ratio >= 0.7) {
            return 0.75;
        }
        if ($ratio >= 0.5) {
            return 0.5;
        }

        return 0.2;
    }

    private function scoreLocationFit(string $required, string $candidate, string $pool): float
    {
        if ($required === '') {
            return 0;
        }

        if ($this->containsPhrase($candidate, $required) || $this->containsPhrase($pool, $required)) {
            return 1;
        }

        if (
            str_contains($required, 'casablanca') &&
            (str_contains($candidate, 'casa') || str_contains($pool, 'casa'))
        ) {
            return 0.9;
        }

        return $this->phraseSimilarity($required, $candidate, $pool);
    }

    private function scoreAvailabilityFit(string $required, string $candidate, string $pool): float
    {
        if ($required === '') {
            return 0;
        }

        $required = $this->normalizeText($required);
        $candidate = $this->normalizeText($candidate);
        $pool = $this->normalizeText($pool);

        if ($candidate !== '' && ($this->containsPhrase($candidate, $required) || $this->containsPhrase($pool, $required))) {
            return 1;
        }

        if (
            (str_contains($required, 'immediat') && (str_contains($candidate, 'immediat') || str_contains($pool, 'immediat'))) ||
            (str_contains($required, 'rapid') && (str_contains($candidate, 'rapid') || str_contains($pool, 'rapid')))
        ) {
            return 0.85;
        }

        return $this->phraseSimilarity($required, $candidate, $pool);
    }

    private function educationRank(string $text): int
    {
        $text = $this->normalizeText($text);

        $map = [
            1 => ['niveau bac', 'bac'],
            2 => ['bac+2', 'bac 2', 'dut', 'bts', 'deust', 'ts', 'technicien specialise', 'technicien spécialisé'],
            3 => ['bac+3', 'bac 3', 'licence', 'bachelor'],
            4 => ['bac+5', 'bac 5', 'master', 'ingenieur', 'ingénieur', 'cycle ingenieur', 'cycle ingénieur'],
            5 => ['doctorat', 'phd'],
        ];

        foreach (array_reverse($map, true) as $rank => $patterns) {
            foreach ($patterns as $pattern) {
                if (str_contains($text, $this->normalizeText($pattern))) {
                    return $rank;
                }
            }
        }

        return 0;
    }

    private function sameTitleFamily(string $required, string $candidate, string $pool): bool
    {
        $required = $this->normalizeText($required);
        $candidate = $this->normalizeText($candidate);
        $pool = $this->normalizeText($pool);

        foreach ($this->titleFamilies as $familyTerms) {
            $reqFound = false;
            $candFound = false;

            foreach ($familyTerms as $term) {
                $term = $this->normalizeText($term);

                if (str_contains($required, $term)) {
                    $reqFound = true;
                }

                if (str_contains($candidate, $term) || str_contains($pool, $term)) {
                    $candFound = true;
                }
            }

            if ($reqFound && $candFound) {
                return true;
            }
        }

        return false;
    }

    private function isSiblingRole(string $required, string $candidate, string $pool): bool
    {
        $required = $this->normalizeText($required);
        $candidate = $this->normalizeText($candidate);
        $pool = $this->normalizeText($pool);

        $siblings = $this->roleSiblings[$required] ?? [];

        foreach ($siblings as $sibling) {
            $sibling = $this->normalizeText($sibling);

            if (str_contains($candidate, $sibling) || str_contains($pool, $sibling)) {
                return true;
            }
        }

        return false;
    }

    private function containsPhrase(string $haystack, string $phrase): bool
    {
        return preg_match('/(^|\s)' . preg_quote($phrase, '/') . '($|\s)/u', $haystack) === 1;
    }

    private function tokenOverlap(string $needle, string $haystack): float
    {
        $a = array_values(array_filter(explode(' ', $this->normalizeText($needle))));
        $b = array_values(array_filter(explode(' ', $this->normalizeText($haystack))));

        if (empty($a) || empty($b)) {
            return 0;
        }

        return count(array_intersect($a, $b)) / count($a);
    }

    private function phraseSimilarity(string $required, string $candidate, string $pool): float
    {
        $required = $this->normalizeText($required);
        $candidate = $this->normalizeText($candidate);

        if ($required === '') {
            return 0;
        }

        similar_text($required, $candidate, $pct);
        $char = $pct / 100;
        $poolRatio = $this->tokenOverlap($required, $pool);

        $reqTokens = array_unique(array_filter(explode(' ', $required)));
        $candTokens = array_unique(array_filter(explode(' ', $candidate)));
        $inter = array_intersect($reqTokens, $candTokens);
        $union = array_unique(array_merge($reqTokens, $candTokens));
        $jaccard = !empty($union) ? count($inter) / count($union) : 0;

        return min(1, max(($char * 0.22) + ($jaccard * 0.33) + ($poolRatio * 0.45), $poolRatio));
    }

    private function extractYears(string $text): float
    {
        $text = $this->normalizeText($text);
        $years = 0.0;

        if (preg_match('/(\d+(?:[\.,]\d+)?)\s*(ans|an|years|year)/u', $text, $m)) {
            $years = (float) str_replace(',', '.', $m[1]);
        }

        if (preg_match('/(\d+)\s*(mois|month|months)/u', $text, $m)) {
            $years += ((int) $m[1]) / 12;
        }

        if ($years > 0) {
            return round($years, 1);
        }

        if (preg_match('/(\d{4})\s*[-–]\s*(\d{4}|present|current|now|aujourd hui)/u', $text, $m)) {
            $start = (int) $m[1];
            $end = is_numeric($m[2]) ? (int) $m[2] : (int) date('Y');
            if ($end >= $start) {
                return round((float) ($end - $start), 1);
            }
        }

        return 0.0;
    }
}