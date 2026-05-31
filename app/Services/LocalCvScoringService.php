<?php

namespace App\Services;

class LocalCvScoringService
{
    protected array $synonyms;
    protected array $titleFamilies;
    protected array $roleSiblings;
    protected array $specificTitleTokens;
    protected array $titleConflicts;

    public function __construct()
    {
        $this->synonyms = config('cv_synonyms.synonyms', []);
        $this->titleFamilies = config('cv_synonyms.title_families', []);
        $this->roleSiblings = config('cv_synonyms.role_siblings', []);
        $this->specificTitleTokens = config('cv_synonyms.specific_title_tokens', []);
        $this->titleConflicts = config('cv_synonyms.title_conflicts', []);
    }

    public function score(array $requirements, array $profile): array
    {
        $breakdown = [
            'title' => 0,
            'education' => 0,
            'experience' => 0,
            'age' => 0,
            'must_have_skills' => 0,
            'nice_to_have_skills' => 0,
            'languages' => 0,
            'location' => 0,
            'availability' => 0,
            'soft_skills' => 0,
            'consistency_bonus' => 0,
        ];

        $weights = [
            'title' => 25,
            'education' => 9,
            'experience' => 18,
            'age' => 6,
            'must_have_skills' => 23,
            'nice_to_have_skills' => 5,
            'languages' => 5,
            'location' => 4,
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
            ?? $profile['current_title']
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

        $profileLocation = $this->normalizeText(implode(' ', array_filter([
            $profile['location'] ?? '',
            $profile['city'] ?? '',
            is_array($profile['locations'] ?? null) ? implode(' ', $profile['locations']) : ($profile['locations'] ?? ''),
        ])));

        $profileAvailability = $this->normalizeText((string) ($profile['availability'] ?? ''));

        $profilePool = $this->normalizeText(implode(' ', array_filter([
            $profileTitle,
            $profileEducation,
            $profileSkills,
            $profileSoftSkills,
            $profileLanguages,
            $profileSummary,
            $profileLocation,
            $profileAvailability,
            is_array($profile['certifications'] ?? null) ? implode(' ', $profile['certifications']) : ($profile['certifications'] ?? ''),
            is_array($profile['industries'] ?? null) ? implode(' ', $profile['industries']) : ($profile['industries'] ?? ''),
            is_array($profile['experiences'] ?? null) ? json_encode($profile['experiences'], JSON_UNESCAPED_UNICODE) : '',
            (string) ($profile['raw_text'] ?? ''),
        ])));

        /*
        |--------------------------------------------------------------------------
        | TITLE
        |--------------------------------------------------------------------------
        */
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
            } else {
                $summaryParts[] = 'poste faible ou différent';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | EDUCATION
        |--------------------------------------------------------------------------
        */
        $reqEducation = $this->normalizeText((string) ($requirements['education'] ?? ''));

        if ($reqEducation !== '') {
            $activeWeights['education'] = $weights['education'];

            $ratio = $this->scoreEducationFit($reqEducation, $profileEducation, $profilePool);
            $breakdown['education'] = round($ratio * $weights['education'], 2);

            if ($ratio >= 0.8) {
                $summaryParts[] = 'formation adaptée';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | EXPERIENCE
        |--------------------------------------------------------------------------
        */
        $reqExp = $this->extractRequiredExperience($requirements);
        $cvExpInfo = $this->extractCandidateExperienceDetails($profile, $profilePool);
        $cvExp = $cvExpInfo['years'];

        if ($reqExp !== null) {
            $activeWeights['experience'] = $weights['experience'];

            $ratio = $this->scoreExperienceFit($reqExp, $cvExp);

            if ($cvExpInfo['inferred'] && ($cvExpInfo['confidence'] ?? 1) < 0.8) {
                $ratio *= 0.92;
            }

            $breakdown['experience'] = round($ratio * $weights['experience'], 2);

            if ($ratio >= 0.95) {
                $summaryParts[] = 'expérience conforme';
            } elseif ($ratio >= 0.75) {
                $summaryParts[] = 'expérience proche du besoin';
            } elseif ($ratio >= 0.45) {
                $summaryParts[] = 'expérience partielle';
            } else {
                $summaryParts[] = 'expérience insuffisante';
            }

            if ($cvExpInfo['inferred']) {
                $summaryParts[] = 'expérience estimée depuis les dates du CV';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | AGE
        |--------------------------------------------------------------------------
        */
        $ageReq = $this->normalizeAgeRequirement($requirements['age_requirement'] ?? ($requirements['age_text'] ?? null));
        $cvAgeInfo = $this->extractCandidateAgeDetails($profile, $profilePool);
        $cvAge = $cvAgeInfo['age'];

        if ($ageReq['has_requirement']) {
            $activeWeights['age'] = $weights['age'];

            $ratio = $this->scoreAgeFit($ageReq, $cvAge);

            if ($cvAgeInfo['inferred'] && ($cvAgeInfo['confidence'] ?? 1) < 0.9) {
                $ratio *= 0.94;
            }

            $breakdown['age'] = round($ratio * $weights['age'], 2);

            if ($ratio >= 1) {
                $summaryParts[] = 'âge conforme';
            } elseif ($ratio >= 0.6) {
                $summaryParts[] = 'âge proche du besoin';
            }

            if ($cvAgeInfo['inferred']) {
                $summaryParts[] = 'âge estimé depuis la date de naissance';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | SKILLS
        |--------------------------------------------------------------------------
        */
        $must = $this->uniqueKeywords($requirements['must_have_skills'] ?? []);
        $nice = $this->uniqueKeywords(array_merge(
            $this->toKeywordArray($requirements['nice_to_have_skills'] ?? []),
            $this->toKeywordArray($requirements['mission_keywords'] ?? [])
        ));

        if (!empty($must)) {
            $activeWeights['must_have_skills'] = $weights['must_have_skills'];

            $mustRatio = $this->scoreKeywordsStrict($must, $profilePool);
            $breakdown['must_have_skills'] = round($mustRatio * $weights['must_have_skills'], 2);

            if ($mustRatio >= 0.8) {
                $summaryParts[] = 'compétences clés bien couvertes';
            } elseif ($mustRatio >= 0.55) {
                $summaryParts[] = 'compétences clés partiellement couvertes';
            }
        }

        if (!empty($nice)) {
            $activeWeights['nice_to_have_skills'] = $weights['nice_to_have_skills'];

            $niceRatio = $this->scoreKeywordsFlexible($nice, $profilePool);
            $breakdown['nice_to_have_skills'] = round($niceRatio * $weights['nice_to_have_skills'], 2);
        }

        /*
        |--------------------------------------------------------------------------
        | LANGUAGES
        |--------------------------------------------------------------------------
        */
        $langs = $this->uniqueKeywords($requirements['languages'] ?? []);

        if (!empty($langs)) {
            $activeWeights['languages'] = $weights['languages'];

            $langRatio = $this->scoreKeywordsStrict($langs, $profileLanguages . ' ' . $profilePool);
            $breakdown['languages'] = round($langRatio * $weights['languages'], 2);
        }

        /*
        |--------------------------------------------------------------------------
        | LOCATION
        |--------------------------------------------------------------------------
        */
        $reqLocations = $this->extractRequiredLocations($requirements);

        if (!empty($reqLocations)) {
            $activeWeights['location'] = $weights['location'];

            $ratio = $this->scoreMultiLocationFit($reqLocations, $profileLocation, $profilePool);
            $breakdown['location'] = round($ratio * $weights['location'], 2);
        }

        /*
        |--------------------------------------------------------------------------
        | AVAILABILITY
        |--------------------------------------------------------------------------
        */
        $reqAvailability = $this->normalizeText((string) ($requirements['availability'] ?? ''));

        if ($reqAvailability !== '') {
            $activeWeights['availability'] = $weights['availability'];

            $ratio = $this->scoreAvailabilityFit($reqAvailability, $profileAvailability, $profilePool);
            $breakdown['availability'] = round($ratio * $weights['availability'], 2);
        }

        /*
        |--------------------------------------------------------------------------
        | SOFT SKILLS
        |--------------------------------------------------------------------------
        */
        $soft = $this->uniqueKeywords($requirements['soft_skills'] ?? []);

        if (!empty($soft)) {
            $activeWeights['soft_skills'] = $weights['soft_skills'];

            $softRatio = $this->scoreKeywordsFlexible($soft, $profileSoftSkills . ' ' . $profilePool);
            $breakdown['soft_skills'] = round($softRatio * $weights['soft_skills'], 2);
        }

        /*
        |--------------------------------------------------------------------------
        | CONSISTENCY BONUS
        |--------------------------------------------------------------------------
        */
        $hasCoreRequirements = !empty(array_diff(array_keys($activeWeights), ['consistency_bonus']));

        if ($hasCoreRequirements) {
            $activeWeights['consistency_bonus'] = $weights['consistency_bonus'];
            $bonus = 0;

            if (isset($activeWeights['title']) && ($breakdown['title'] ?? 0) >= ($weights['title'] * 0.62)) {
                $bonus += 0.8;
            }

            if (isset($activeWeights['experience']) && ($breakdown['experience'] ?? 0) >= ($weights['experience'] * 0.62)) {
                $bonus += 0.5;
            }

            if (isset($activeWeights['must_have_skills']) && ($breakdown['must_have_skills'] ?? 0) >= ($weights['must_have_skills'] * 0.62)) {
                $bonus += 0.5;
            }

            if (isset($activeWeights['location']) && ($breakdown['location'] ?? 0) >= ($weights['location'] * 0.8)) {
                $bonus += 0.2;
            }

            $breakdown['consistency_bonus'] = round(min($weights['consistency_bonus'], $bonus), 2);
        }

        $rawScore = array_sum($breakdown);
        $maxScore = max(1, array_sum($activeWeights));
        $score = round(min(100, ($rawScore / $maxScore) * 100), 2);

        $summary = !empty($summaryParts)
            ? ucfirst(implode(', ', array_slice(array_unique($summaryParts), 0, 4))) . '.'
            : 'Évaluation locale fondée sur le poste, les compétences, l’expérience, l’âge, le lieu et la cohérence globale.';

        return [
            'score' => $score,
            'breakdown' => $breakdown,
            'summary' => $summary,
            'explanations' => $this->buildExplanations($breakdown, $weights, $reqRole, $reqExp, $cvExp, $reqLocations, $cvExpInfo, $cvAgeInfo),
            'meta' => [
                'raw_score' => round($rawScore, 2),
                'max_score' => round($maxScore, 2),
                'required_experience_years' => $reqExp,
                'candidate_experience_years' => $cvExp,
                'candidate_experience_inferred' => $cvExpInfo['inferred'],
                'candidate_experience_source' => $cvExpInfo['source'],
                'required_age' => $ageReq,
                'candidate_age' => $cvAge,
                'candidate_age_inferred' => $cvAgeInfo['inferred'],
                'candidate_age_source' => $cvAgeInfo['source'],
                'required_locations' => $reqLocations,
            ],
        ];
    }

    private function normalizeText(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');

        if (class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($text, \Normalizer::FORM_D);

            if (is_string($normalized)) {
                $text = preg_replace('/\p{Mn}+/u', '', $normalized) ?? $normalized;
            }
        }

        $replacements = [
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'à' => 'a', 'â' => 'a', 'ä' => 'a',
            'î' => 'i', 'ï' => 'i',
            'ô' => 'o', 'ö' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
            'œ' => 'oe', 'æ' => 'ae',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'à' => 'a', 'â' => 'a',
            'î' => 'i', 'ï' => 'i',
            'ô' => 'o', 'ö' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
            'œ' => 'oe',
        ];

        $text = strtr($text, $replacements);
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

        return array_values(array_unique(array_filter(array_map(
            fn ($v) => $this->normalizeText((string) $v),
            $variants
        ))));
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

        $exactRole = $this->containsPhrase($candidate, $required);
        $poolMention = !$exactRole && $this->containsPhrase($pool, $required);

        $familyBoost = $this->sameTitleFamily($required, $candidate, $pool) ? 0.08 : 0;
        $siblingPenalty = $this->isSiblingRole($required, $candidate, $pool) ? 0.35 : 0;
        $specificPenalty = $this->specificTokenPenalty($required, $candidate, $pool);
        $conflictPenalty = $this->sameConflictGroup($required, $candidate, $pool) ? 0 : 0.34;

        if ($exactRole) {
            $direct = max($direct, 0.96);
        } elseif ($poolMention) {
            $direct = min(max($direct, 0.72), 0.82);
        }

        return max(0, min(1, $direct + $familyBoost - $siblingPenalty - $specificPenalty - $conflictPenalty));
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

    private function scoreExperienceFit(float $requiredYears, ?float $candidateYears): float
    {
        if ($requiredYears <= 0) {
            return 0;
        }

        if ($candidateYears === null || $candidateYears <= 0) {
            return 0.12;
        }

        $ratio = $candidateYears / $requiredYears;

        if ($ratio >= 1.0) {
            return 1.0;
        }

        if ($ratio >= 0.85) {
            return 0.9;
        }

        if ($ratio >= 0.70) {
            return 0.75;
        }

        if ($ratio >= 0.50) {
            return 0.5;
        }

        return 0.2;
    }

    private function scoreAgeFit(array $requirement, ?int $candidateAge): float
    {
        if (!$requirement['has_requirement']) {
            return 0;
        }

        if (!$candidateAge) {
            return 0.35;
        }

        $min = $requirement['min'];
        $max = $requirement['max'];

        if ($min !== null && $candidateAge < $min) {
            $distance = $min - $candidateAge;

            if ($distance <= 2) {
                return 0.75;
            }

            if ($distance <= 5) {
                return 0.45;
            }

            return 0.15;
        }

        if ($max !== null && $candidateAge > $max) {
            $distance = $candidateAge - $max;

            if ($distance <= 2) {
                return 0.75;
            }

            if ($distance <= 5) {
                return 0.45;
            }

            return 0.15;
        }

        return 1.0;
    }

    private function scoreMultiLocationFit(array $requiredLocations, string $candidate, string $pool): float
    {
        if (empty($requiredLocations)) {
            return 0;
        }

        $best = 0;

        foreach ($requiredLocations as $location) {
            $location = $this->normalizeText($location);

            if ($location === '') {
                continue;
            }

            $score = $this->scoreLocationFit($location, $candidate, $pool);
            $best = max($best, $score);
        }

        return $best;
    }

    private function scoreLocationFit(string $required, string $candidate, string $pool): float
    {
        $required = $this->normalizeText($required);
        $candidate = $this->normalizeText($candidate);
        $pool = $this->normalizeText($pool);

        if ($required === '') {
            return 0;
        }

        $aliases = [
            'casablanca' => ['casa', 'casablanca', 'ain sebaa', 'sidi maarouf', 'bouskoura', 'nouaceur'],
            'rabat' => ['rabat', 'sale', 'salé', 'temara', 'temara'],
            'tanger' => ['tanger', 'tangier', 'tanja'],
            'marrakech' => ['marrakech', 'marrakesh'],
            'fes' => ['fes', 'fès'],
            'meknes' => ['meknes', 'meknès'],
            'agadir' => ['agadir'],
            'kenitra' => ['kenitra', 'kénitra'],
            'mohammedia' => ['mohammedia'],
            'el jadida' => ['el jadida', 'eljadida'],
        ];

        $requiredSet = [$required];

        foreach ($aliases as $city => $list) {
            if ($required === $this->normalizeText($city) || in_array($required, array_map(fn ($v) => $this->normalizeText($v), $list), true)) {
                $requiredSet = array_map(fn ($v) => $this->normalizeText($v), $list);
                break;
            }
        }

        foreach ($requiredSet as $city) {
            if ($this->containsPhrase($candidate, $city) || $this->containsPhrase($pool, $city)) {
                return 1;
            }
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
            (str_contains($required, 'rapid') && (str_contains($candidate, 'rapid') || str_contains($pool, 'rapid'))) ||
            (str_contains($required, 'asap') && (str_contains($candidate, 'asap') || str_contains($pool, 'asap')))
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

                if ($term !== '' && str_contains($required, $term)) {
                    $reqFound = true;
                }

                if ($term !== '' && (str_contains($candidate, $term) || str_contains($pool, $term))) {
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

            if ($sibling !== '' && (str_contains($candidate, $sibling) || str_contains($pool, $sibling))) {
                return true;
            }
        }

        return false;
    }

    private function containsPhrase(string $haystack, string $phrase): bool
    {
        $haystack = $this->normalizeText($haystack);
        $phrase = $this->normalizeText($phrase);

        if ($haystack === '' || $phrase === '') {
            return false;
        }

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
        $pool = $this->normalizeText($pool);

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

        return min(1, max(($char * 0.20) + ($jaccard * 0.30) + ($poolRatio * 0.50), $poolRatio));
    }

    private function extractRequiredExperience(array $requirements): ?float
    {
        if (isset($requirements['min_experience_years']) && is_numeric($requirements['min_experience_years'])) {
            return (float) $requirements['min_experience_years'];
        }

        $text = (string) (
            $requirements['experience_text']
            ?? $requirements['experience_years']
            ?? $requirements['min_experience_years']
            ?? ''
        );

        return $this->extractYears($text, true);
    }

    private function extractCandidateExperience(array $profile, string $pool): ?float
    {
        return $this->extractCandidateExperienceDetails($profile, $pool)['years'];
    }

    private function extractCandidateExperienceDetails(array $profile, string $pool): array
    {
        foreach ([
            $profile['years_experience'] ?? null,
            $profile['experience_years'] ?? null,
            $profile['total_experience'] ?? null,
        ] as $value) {
            if (is_numeric($value)) {
                return [
                    'years' => (float) $value,
                    'inferred' => false,
                    'source' => 'champ structure',
                    'confidence' => 1.0,
                ];
            }

            if (is_string($value)) {
                $years = $this->extractYears($value, false);

                if ($years !== null) {
                    return [
                        'years' => $years,
                        'inferred' => false,
                        'source' => 'texte experience',
                        'confidence' => 0.9,
                    ];
                }
            }
        }

        $periodYears = $this->estimateExperienceFromPeriods($pool);

        if ($periodYears !== null) {
            return [
                'years' => $periodYears,
                'inferred' => true,
                'source' => 'periodes professionnelles',
                'confidence' => 0.78,
            ];
        }

        $levelYears = $this->estimateExperienceFromLevel($pool);

        if ($levelYears !== null) {
            return [
                'years' => $levelYears,
                'inferred' => true,
                'source' => 'niveau seniorite',
                'confidence' => 0.68,
            ];
        }

        $years = $this->extractYears($pool, false);

        return [
            'years' => $years,
            'inferred' => $years !== null,
            'source' => $years !== null ? 'texte du CV' : null,
            'confidence' => $years !== null ? 0.72 : 0.0,
        ];
    }

    private function extractYears(string $text, bool $forRequirement = false): ?float
    {
        $text = $this->normalizeText($text);

        if ($text === '') {
            return null;
        }

        if (str_contains($text, 'debutant') || str_contains($text, 'debutante')) {
            return 0.0;
        }

        $levelYears = $this->estimateExperienceFromLevel($text);

        if ($levelYears !== null) {
            return $levelYears;
        }

        if (preg_match('/(\d+(?:[\.,]\d+)?)\s*(?:-|a|à|to)\s*(\d+(?:[\.,]\d+)?)\s*(ans|an|years|year)/u', $text, $m)) {
            return (float) str_replace(',', '.', $forRequirement ? $m[1] : $m[2]);
        }

        if (preg_match('/(?:minimum|min|au moins|plus de)\s*(\d+(?:[\.,]\d+)?)/u', $text, $m)) {
            return (float) str_replace(',', '.', $m[1]);
        }

        if (preg_match('/(\d+(?:[\.,]\d+)?)\s*(ans|an|years|year)/u', $text, $m)) {
            return (float) str_replace(',', '.', $m[1]);
        }

        if (preg_match('/(\d+)\s*(mois|month|months)/u', $text, $m)) {
            return round(((int) $m[1]) / 12, 1);
        }

        return null;
    }

    private function estimateExperienceFromPeriods(string $text): ?float
    {
        $text = $this->normalizeText($text);
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('n');
        $periods = [];

        if (preg_match_all('/\b(20[0-2]\d|19[7-9]\d)\s*[-–—]\s*(20[0-2]\d|19[7-9]\d|present|current|now|actuel|aujourd hui|presentement)\b/u', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $start = (int) $match[1];
                $end = is_numeric($match[2]) ? (int) $match[2] : $currentYear;

                if ($start >= 1970 && $start <= $currentYear && $end >= $start && $end <= $currentYear + 1) {
                    $periods[] = [$start * 12 + 1, $end * 12 + 12];
                }
            }
        }

        if (preg_match_all('/\b(\d{1,2})[\/\-.](20[0-2]\d|19[7-9]\d)\s*(?:-|a|à|to)\s*(?:(\d{1,2})[\/\-.](20[0-2]\d|19[7-9]\d)|present|current|now|actuel|aujourd hui|presentement|aujourd)\b/u', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $startMonth = max(1, min(12, (int) $match[1]));
                $startYear = (int) $match[2];
                $endMonth = !empty($match[3]) ? max(1, min(12, (int) $match[3])) : $currentMonth;
                $endYear = !empty($match[4]) ? (int) $match[4] : $currentYear;

                if ($startYear >= 1970 && $startYear <= $currentYear && $endYear >= $startYear && $endYear <= $currentYear + 1) {
                    $periods[] = [$startYear * 12 + $startMonth, $endYear * 12 + $endMonth];
                }
            }
        }

        $monthNames = '(?:jan(?:vier)?|fev(?:rier)?|fevrier|mar(?:s)?|avr(?:il)?|mai|juin|juil(?:let)?|aout|sep(?:tembre)?|oct(?:obre)?|nov(?:embre)?|dec(?:embre)?|january|february|march|april|may|june|july|august|september|october|november|december)';

        if (preg_match_all('/\b(' . $monthNames . ')\s+(20[0-2]\d|19[7-9]\d)\s*(?:-|a|à|to)\s*(?:(' . $monthNames . ')\s+)?(20[0-2]\d|19[7-9]\d|present|current|now|actuel|aujourd hui|presentement|aujourd)\b/u', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $startMonth = $this->monthNumber($match[1]);
                $startYear = (int) $match[2];
                $endIsYear = is_numeric($match[4]);
                $endMonth = $endIsYear ? $this->monthNumber($match[3] ?: 'decembre') : $currentMonth;
                $endYear = $endIsYear ? (int) $match[4] : $currentYear;

                if ($startMonth && $endMonth && $startYear >= 1970 && $startYear <= $currentYear && $endYear >= $startYear) {
                    $periods[] = [$startYear * 12 + $startMonth, $endYear * 12 + $endMonth];
                }
            }
        }

        if (preg_match_all('/(?:depuis|since)\s+(20[0-2]\d|19[7-9]\d)\b/u', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $start = (int) $match[1];

                if ($start >= 1970 && $start <= $currentYear) {
                    $periods[] = [$start * 12 + 1, $currentYear * 12 + $currentMonth];
                }
            }
        }

        if (empty($periods)) {
            return null;
        }

        usort($periods, fn ($a, $b) => $a[0] <=> $b[0]);

        $merged = [];

        foreach ($periods as $period) {
            if (empty($merged) || $period[0] > $merged[count($merged) - 1][1]) {
                $merged[] = $period;
            } else {
                $merged[count($merged) - 1][1] = max($merged[count($merged) - 1][1], $period[1]);
            }
        }

        $years = 0;

        foreach ($merged as [$start, $end]) {
            $years += max(0, (($end - $start) + 1) / 12);
        }

        return $years > 0 ? round(min($years, 45), 1) : null;
    }

    private function estimateExperienceFromLevel(string $text): ?float
    {
        $text = $this->normalizeText($text);

        if (preg_match('/\b(debutant|debutante|junior|stagiaire|entry level|entry-level|intern|trainee)\b/u', $text)) {
            return 1.0;
        }

        if (preg_match('/\b(confirme|confirmee|intermediaire|intermediate|mid level|mid-level)\b/u', $text)) {
            return 4.0;
        }

        if (preg_match('/\b(senior|experimente|experimentee|expert|lead|principal)\b/u', $text)) {
            return 6.0;
        }

        return null;
    }

    private function monthNumber(string $month): ?int
    {
        $month = $this->normalizeText($month);
        $map = [
            'jan' => 1, 'janvier' => 1, 'january' => 1,
            'fev' => 2, 'fevrier' => 2, 'february' => 2,
            'mar' => 3, 'mars' => 3, 'march' => 3,
            'avr' => 4, 'avril' => 4, 'april' => 4,
            'mai' => 5, 'may' => 5,
            'juin' => 6, 'june' => 6,
            'juil' => 7, 'juillet' => 7, 'july' => 7,
            'aout' => 8, 'august' => 8,
            'sep' => 9, 'septembre' => 9, 'september' => 9,
            'oct' => 10, 'octobre' => 10, 'october' => 10,
            'nov' => 11, 'novembre' => 11, 'november' => 11,
            'dec' => 12, 'decembre' => 12, 'december' => 12,
        ];

        return $map[$month] ?? null;
    }

    private function normalizeAgeRequirement($value): array
    {
        if (is_array($value)) {
            return [
                'min' => isset($value['min']) && $value['min'] !== null ? (int) $value['min'] : null,
                'max' => isset($value['max']) && $value['max'] !== null ? (int) $value['max'] : null,
                'text' => $value['text'] ?? '',
                'has_requirement' => !empty($value['min']) || !empty($value['max']) || !empty($value['text']),
            ];
        }

        $text = $this->normalizeText((string) $value);

        if ($text === '') {
            return [
                'min' => null,
                'max' => null,
                'text' => '',
                'has_requirement' => false,
            ];
        }

        if (preg_match('/(\d{1,2})\s*(?:-|a|à|to)\s*(\d{1,2})/u', $text, $m)) {
            return [
                'min' => min((int) $m[1], (int) $m[2]),
                'max' => max((int) $m[1], (int) $m[2]),
                'text' => $text,
                'has_requirement' => true,
            ];
        }

        if (preg_match('/(?:moins de|max|maximum|jusqu|inferieur|inférieur)\s*(?:a|à|de)?\s*(\d{1,2})/u', $text, $m)) {
            return [
                'min' => null,
                'max' => (int) $m[1],
                'text' => $text,
                'has_requirement' => true,
            ];
        }

        if (preg_match('/(?:plus de|min|minimum|au moins|superieur|supérieur)\s*(?:a|à|de)?\s*(\d{1,2})/u', $text, $m)) {
            return [
                'min' => (int) $m[1],
                'max' => null,
                'text' => $text,
                'has_requirement' => true,
            ];
        }

        if (preg_match('/\b(\d{1,2})\b/u', $text, $m)) {
            return [
                'min' => null,
                'max' => (int) $m[1],
                'text' => $text,
                'has_requirement' => true,
            ];
        }

        return [
            'min' => null,
            'max' => null,
            'text' => $text,
            'has_requirement' => false,
        ];
    }

    private function extractCandidateAge(array $profile, string $pool): ?int
    {
        return $this->extractCandidateAgeDetails($profile, $pool)['age'];
    }

    private function extractCandidateAgeDetails(array $profile, string $pool): array
    {
        foreach ([
            $profile['age'] ?? null,
            $profile['candidate_age'] ?? null,
        ] as $value) {
            if (is_numeric($value)) {
                $age = (int) $value;

                if ($age >= 16 && $age <= 70) {
                    return [
                        'age' => $age,
                        'inferred' => false,
                        'source' => 'champ structure',
                        'confidence' => 1.0,
                    ];
                }
            }
        }

        if (preg_match('/\b(\d{1,2})\s*(?:ans|years old|year old)\b/u', $pool, $m)) {
            $age = (int) $m[1];

            if ($age >= 16 && $age <= 70) {
                return [
                    'age' => $age,
                    'inferred' => false,
                    'source' => 'age explicite',
                    'confidence' => 0.92,
                ];
            }
        }

        $birthDate = $this->extractBirthDate($pool);

        if ($birthDate) {
            $age = $this->ageFromDate($birthDate);

            if ($age !== null) {
                return [
                    'age' => $age,
                    'inferred' => true,
                    'source' => 'date de naissance',
                    'confidence' => 0.86,
                ];
            }
        }

        return [
            'age' => null,
            'inferred' => false,
            'source' => null,
            'confidence' => 0.0,
        ];
    }

    private function extractBirthDate(string $text): ?array
    {
        $text = $this->normalizeText($text);
        $birthPrefix = '(?:ne|nee|naissance|date de naissance|born|birth|date of birth)';

        if (preg_match('/' . $birthPrefix . '[^\d]{0,24}(\d{1,2})[\/\-.](\d{1,2})[\/\-.](19[5-9]\d|20[0-1]\d)/u', $text, $m)) {
            return ['year' => (int) $m[3], 'month' => (int) $m[2], 'day' => (int) $m[1]];
        }

        if (preg_match('/' . $birthPrefix . '[^\d]{0,24}(19[5-9]\d|20[0-1]\d)[\/\-.](\d{1,2})[\/\-.](\d{1,2})/u', $text, $m)) {
            return ['year' => (int) $m[1], 'month' => (int) $m[2], 'day' => (int) $m[3]];
        }

        if (preg_match('/' . $birthPrefix . '[^\d]{0,24}(19[5-9]\d|20[0-1]\d)\b/u', $text, $m)) {
            return ['year' => (int) $m[1], 'month' => 1, 'day' => 1];
        }

        return null;
    }

    private function ageFromDate(array $birthDate): ?int
    {
        $year = (int) ($birthDate['year'] ?? 0);
        $month = max(1, min(12, (int) ($birthDate['month'] ?? 1)));
        $day = max(1, min(31, (int) ($birthDate['day'] ?? 1)));

        if ($year < 1950 || $year > (int) date('Y')) {
            return null;
        }

        $age = (int) date('Y') - $year;

        if (((int) date('n') < $month) || ((int) date('n') === $month && (int) date('j') < $day)) {
            $age--;
        }

        return $age >= 16 && $age <= 70 ? $age : null;
    }

    private function extractRequiredLocations(array $requirements): array
    {
        $locations = [];

        if (!empty($requirements['locations']) && is_array($requirements['locations'])) {
            $locations = array_merge($locations, $requirements['locations']);
        }

        if (!empty($requirements['location'])) {
            $locations = array_merge($locations, preg_split('/[,;|\/]+/u', (string) $requirements['location']));
        }

        return array_values(array_unique(array_filter(array_map(
            fn ($v) => $this->normalizeText((string) $v),
            $locations
        ))));
    }

    private function specificTokenPenalty(string $required, string $candidate, string $pool): float
    {
        $requiredTokens = $this->extractSpecificTitleTokens($required);

        if (empty($requiredTokens)) {
            return 0;
        }

        $haystack = $candidate . ' ' . $pool;
        $misses = 0;

        foreach ($requiredTokens as $token) {
            if (!$this->containsPhrase($haystack, $token)) {
                $misses++;
            }
        }

        return min(0.36, $misses * 0.12);
    }

    private function extractSpecificTitleTokens(string $text): array
    {
        $normalized = $this->normalizeText($text);
        $tokens = [];

        foreach ($this->specificTitleTokens as $token) {
            $token = $this->normalizeText((string) $token);

            if ($token !== '' && $this->containsPhrase($normalized, $token)) {
                $tokens[] = $token;
            }
        }

        return array_values(array_unique($tokens));
    }

    private function sameConflictGroup(string $required, string $candidate, string $pool): bool
    {
        $haystack = $candidate . ' ' . $pool;

        foreach ($this->titleConflicts as $groups) {
            $requiredGroup = null;
            $candidateGroup = null;

            foreach ($groups as $groupName => $terms) {
                foreach ($terms as $term) {
                    $term = $this->normalizeText((string) $term);

                    if ($term !== '' && $requiredGroup === null && $this->containsPhrase($required, $term)) {
                        $requiredGroup = $groupName;
                    }

                    if ($term !== '' && $candidateGroup === null && $this->containsPhrase($haystack, $term)) {
                        $candidateGroup = $groupName;
                    }
                }
            }

            if ($requiredGroup !== null) {
                return $candidateGroup === null || $candidateGroup === $requiredGroup;
            }
        }

        return true;
    }

    private function buildExplanations(
        array $breakdown,
        array $weights,
        string $reqRole,
        ?float $reqExp,
        ?float $cvExp,
        array $reqLocations,
        array $cvExpInfo = [],
        array $cvAgeInfo = []
    ): array
    {
        $explanations = [];

        if ($reqRole !== '') {
            $ratio = ($breakdown['title'] ?? 0) / max(1, ($weights['title'] ?? 1));
            $explanations['title'] = $ratio >= 0.8
                ? 'Intitule tres proche du besoin.'
                : ($ratio >= 0.5 ? 'Intitule partiellement proche du besoin.' : 'Intitule trop eloigne ou trop generique.');
        }

        if (!is_null($reqExp)) {
            if (is_null($cvExp)) {
                $explanations['experience'] = 'Experience du candidat non detectee clairement.';
            } else {
                $source = !empty($cvExpInfo['inferred'])
                    ? ' estimee par ' . ($cvExpInfo['source'] ?? 'inference')
                    : ' detectee';
                $explanations['experience'] = 'Experience' . $source . ' a ' . round($cvExp, 1) . ' an(s) pour un besoin d environ ' . round($reqExp, 1) . ' an(s).';
            }
        }

        if (!empty($cvAgeInfo['age'])) {
            $source = !empty($cvAgeInfo['inferred'])
                ? ' estime depuis ' . ($cvAgeInfo['source'] ?? 'la date de naissance')
                : ' detecte';
            $explanations['age'] = 'Age' . $source . ' : ' . (int) $cvAgeInfo['age'] . ' ans.';
        }

        if (!empty($reqLocations)) {
            $explanations['location'] = (($breakdown['location'] ?? 0) > 0)
                ? 'Localisation compatible ou proche du besoin.'
                : 'Aucune localisation compatible detectee.';
        }

        $explanations['skills'] = (($breakdown['must_have_skills'] ?? 0) > ($weights['must_have_skills'] ?? 1) * 0.6)
            ? 'Les competences essentielles sont bien representees.'
            : 'Les competences essentielles ne sont que partiellement couvertes.';

        return $explanations;
    }
}
