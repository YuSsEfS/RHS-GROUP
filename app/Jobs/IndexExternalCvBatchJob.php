<?php

namespace App\Jobs;

use App\Models\ExternalCvBatch;
use App\Services\ExternalCvIndexingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IndexExternalCvBatchJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    public int $timeout = 14400;

    public array $backoff = [30, 120, 300];

    public int $chunkSize = 25;

    public function __construct(
        public int $batchId,
        public bool $forceReindex = false,
        ?int $chunkSize = null,
    ) {
        if ($chunkSize !== null) {
            $this->chunkSize = max(1, $chunkSize);
        }

        $this->onConnection('database');
        $this->onQueue('indexing');
    }

    public function handle(ExternalCvIndexingService $indexing): void
    {
        $lock = Cache::lock('external-cv-batch-indexing:' . $this->batchId, 900);

        if (!$lock->get()) {
            return;
        }

        $batch = ExternalCvBatch::find($this->batchId);

        try {
            if (!$batch) {
                return;
            }

            $hasMore = $indexing->indexBatchSlice($batch, $this->forceReindex, $this->chunkSize);

            if ($hasMore) {
                self::dispatch($this->batchId, false, $this->chunkSize)->delay(now()->addSeconds(2));
            }
        } finally {
            optional($lock)->release();
        }
    }

    public function failed(\Throwable $e): void
    {
        $batch = ExternalCvBatch::find($this->batchId);

        if ($batch) {
            $batch->update([
                'status' => 'failed',
                'processing_status' => 'echoue',
                'processing_completed_at' => now(),
                'processing_error_message' => mb_substr($e->getMessage(), 0, 2000),
            ]);
        }

        Log::error('External CV batch indexing failed: ' . $e->getMessage(), [
            'batch_id' => $this->batchId,
            'force_reindex' => $this->forceReindex,
        ]);
    }
}
