<?php

namespace App\Jobs;

use App\Models\Cv;
use App\Models\RecruitmentRequest;
use App\Services\RecruitmentScoringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncCvMatchesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 240;

    public function __construct(
        public int $cvId,
        public array $recruitmentRequestIds,
        public string $reason = 'cv_sync',
    ) {
        $this->onConnection('database');
        $this->onQueue(self::queueNameFor($this->cvId));
    }

    public static function queueNameFor(int $cvId): string
    {
        return 'recruitment-sync-' . $cvId;
    }

    public function handle(RecruitmentScoringService $scoring): array
    {
        $cv = Cv::find($this->cvId);

        if (!$cv) {
            return ['status' => 'missing_cv', 'synced' => 0];
        }

        $synced = 0;

        foreach (array_unique(array_map('intval', $this->recruitmentRequestIds)) as $requestId) {
            $request = RecruitmentRequest::find($requestId);

            if (!$request) {
                continue;
            }

            try {
                if ($scoring->scoreCvAgainstRequest($request, $cv)) {
                    $synced++;

                    if ($request->resolveMatchingStatus() !== RecruitmentRequest::MATCHING_STATUS_PROCESSING) {
                        $request->markMatchingCompleted();
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('CV matching sync item failed', [
                    'cv_id' => $this->cvId,
                    'recruitment_request_id' => $requestId,
                    'reason' => $this->reason,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return ['status' => 'synced', 'synced' => $synced];
    }
}
