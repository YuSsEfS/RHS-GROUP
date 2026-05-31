<?php

namespace App\Jobs;

use App\Models\RecruitmentRequest;
use App\Services\MatchingProgressService;
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
        $this->onConnection('database');
        $this->onQueue(self::queueNameFor($this->recruitmentRequestId));
    }

    public static function queueNameFor(int $recruitmentRequestId): string
    {
        return 'recruitment-' . $recruitmentRequestId;
    }

    public function handle(RecruitmentScoringService $scoring, MatchingProgressService $progress): int
    {
        $request = RecruitmentRequest::find($this->recruitmentRequestId);

        if (!$request) {
            return 0;
        }

        if ($progress->isCancelled($request->id)) {
            $request->markMatchingCancelled();

            return 0;
        }

        $request->markMatchingRunning();

        $matches = $scoring->scoreRequestMatches($request, $this->folderId, [
            'on_start' => fn (int $total) => $progress->start($request->id, $total),
            'on_progress' => fn (int $processed, int $matched, int $total) => $progress->tick($request->id, $processed, $matched, $total),
            'cancelled' => fn () => $progress->isCancelled($request->id),
        ]);

        $request->refresh();

        if ($progress->isCancelled($request->id)) {
            $request->markMatchingCancelled();
        } else {
            $progress->finish($request->id, $matches);
            $request->markMatchingCompleted();
        }

        return $matches;
    }

    public function failed(\Throwable $e): void
    {
        $request = RecruitmentRequest::find($this->recruitmentRequestId);

        if ($request) {
            $request->markMatchingFailed($e);
        }
    }
}
