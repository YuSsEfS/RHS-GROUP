<?php

namespace App\Jobs;

use App\Models\CvMatch;
use App\Services\RecruitmentScoringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeCvMatchWithAiJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 240;

    public function __construct(public int $matchId)
    {
    }

    public function handle(RecruitmentScoringService $scoring): array
    {
        $match = CvMatch::find($this->matchId);

        if (!$match) {
            return ['success' => false, 'message' => 'Match introuvable.'];
        }

        return $scoring->analyzeMatchWithAi($match);
    }
}
