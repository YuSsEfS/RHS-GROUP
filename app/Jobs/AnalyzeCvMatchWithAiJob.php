<?php

namespace App\Jobs;

use App\Models\CvMatch;
use App\Models\RecruitmentRequest;
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
        $this->onConnection('database');
        $this->onQueue('ai');
    }

    public function handle(RecruitmentScoringService $scoring): array
    {
        $match = CvMatch::find($this->matchId);

        if (!$match) {
            return ['success' => false, 'message' => 'Match introuvable.'];
        }

        $match->update([
            'ai_analysis_status' => RecruitmentRequest::JOB_STATUS_RUNNING,
            'ai_analysis_started_at' => $match->ai_analysis_started_at ?: now(),
            'ai_analysis_completed_at' => null,
            'ai_analysis_error_message' => null,
        ]);

        $result = $scoring->analyzeMatchWithAi($match);

        $match->refresh();
        $match->update([
            'ai_analysis_status' => ($result['success'] ?? false)
                ? RecruitmentRequest::JOB_STATUS_DONE
                : RecruitmentRequest::JOB_STATUS_FAILED,
            'ai_analysis_completed_at' => now(),
            'ai_analysis_error_message' => ($result['success'] ?? false)
                ? null
                : mb_substr((string) ($result['message'] ?? 'Analyse IA indisponible.'), 0, 2000),
        ]);

        return $result;
    }

    public function failed(\Throwable $e): void
    {
        $match = CvMatch::find($this->matchId);

        if ($match) {
            $match->update([
                'ai_analysis_status' => RecruitmentRequest::JOB_STATUS_FAILED,
                'ai_analysis_completed_at' => now(),
                'ai_analysis_error_message' => mb_substr($e->getMessage(), 0, 2000),
            ]);
        }
    }
}
