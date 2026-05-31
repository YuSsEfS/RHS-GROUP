<?php

namespace App\Jobs;

use App\Models\CvImportBatch;
use App\Services\CandidateMatchingSyncService;
use App\Services\CvIngestionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessManualCvUploadJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 300;

    public function __construct(
        public string $temporaryPath,
        public string $originalFilename,
        public ?string $mimeType,
        public int $fileSize,
        public array $context = [],
        public ?int $importBatchId = null,
    ) {
        $this->onConnection('database');
        $this->onQueue('indexing');
    }

    public function handle(CvIngestionService $ingestion, CandidateMatchingSyncService $matchingSync): array
    {
        if (!Storage::disk('local')->exists($this->temporaryPath)) {
            $this->markImportBatchFailed('Temporary upload not found.');

            return ['status' => 'failed', 'message' => 'Temporary upload not found.'];
        }

        $binary = Storage::disk('local')->get($this->temporaryPath);

        try {
            $result = $ingestion->importManualCv(
                binary: $binary,
                originalFilename: $this->originalFilename,
                mimeType: $this->mimeType,
                fileSize: $this->fileSize,
                context: $this->context,
            );

            $this->markImportBatchProcessed($result);
            $matchingSync->dispatchForCvId($result['cv_id'] ?? null, 'manual_import');

            return $result;
        } finally {
            Storage::disk('local')->delete($this->temporaryPath);
        }
    }

    public function failed(\Throwable $e): void
    {
        $this->markImportBatchFailed($e->getMessage());

        Log::error('Manual CV upload indexing failed: ' . $e->getMessage(), [
            'temporary_path' => $this->temporaryPath,
            'original_filename' => $this->originalFilename,
            'import_batch_id' => $this->importBatchId,
        ]);
    }

    private function markImportBatchProcessed(array $result = []): void
    {
        if (!$this->importBatchId) {
            return;
        }

        $batch = CvImportBatch::find($this->importBatchId);

        if (!$batch) {
            return;
        }

        if (($result['status'] ?? null) === 'duplicate') {
            $batch->increment('duplicate_files');
        } else {
            $batch->increment('processed_files');
        }

        $batch->refresh();
        $batch->refreshProgressState();
    }

    private function markImportBatchFailed(?string $message = null): void
    {
        if (!$this->importBatchId) {
            return;
        }

        $batch = CvImportBatch::find($this->importBatchId);

        if (!$batch) {
            return;
        }

        $batch->increment('failed_files');
        $batch->refresh();

        if ($message) {
            $batch->forceFill([
                'error_message' => mb_substr($message, 0, 2000),
            ])->saveQuietly();
        }

        $batch->refreshProgressState();
    }
}
