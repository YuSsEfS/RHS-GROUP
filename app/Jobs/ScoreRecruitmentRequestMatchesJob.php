<?php

namespace App\Jobs;

use App\Models\RecruitmentRequest;
use App\Services\RecruitmentScoringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScoreRecruitmentRequestMatchesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 600;

    public function __construct(
        public int $recruitmentRequestId,
        public ?int $folderId = null,
    ) {
    }

    public function handle(RecruitmentScoringService $scoring): int
    {
        $request = RecruitmentRequest::find($this->recruitmentRequestId);

        if (!$request) {
            return 0;
        }

        return $scoring->scoreRequestMatches($request, $this->folderId);
    }
}
