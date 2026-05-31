<?php

namespace App\Jobs;

use App\Models\JobApplication;
use App\Services\CandidateMatchingSyncService;
use App\Services\CvIngestionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncApplicationCvToBankJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 180;

    public function __construct(public int $applicationId)
    {
        $this->onConnection('database');
        $this->onQueue('indexing');
    }

    public function handle(CvIngestionService $ingestion, CandidateMatchingSyncService $matchingSync): array
    {
        $application = JobApplication::find($this->applicationId);

        if (!$application) {
            return ['status' => 'missing_application'];
        }

        $result = $ingestion->syncApplicationCvToBank($application);
        $matchingSync->dispatchForCvId($result['cv_id'] ?? null, 'application_cv_sync');

        return $result;
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Application CV bank sync failed: ' . $e->getMessage(), [
            'application_id' => $this->applicationId,
        ]);
    }
}
