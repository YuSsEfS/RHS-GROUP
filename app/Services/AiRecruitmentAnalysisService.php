<?php

namespace App\Services;

class AiRecruitmentAnalysisService
{
    public function __construct(protected AiFinalCvScoringService $aiScoring)
    {
    }

    public function analyze(
        array $requirements,
        array $profile,
        float $localScore = 0,
        array $localBreakdown = [],
        string $localSummary = ''
    ): array {
        return $this->aiScoring->score(
            $requirements,
            $profile,
            $localScore,
            $localBreakdown,
            $localSummary
        );
    }
}
