<?php

namespace App\Jobs;

use App\Services\CvIngestionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
    ) {
    }

    public function handle(CvIngestionService $ingestion): array
    {
        if (!Storage::disk('local')->exists($this->temporaryPath)) {
            return ['status' => 'failed', 'message' => 'Temporary upload not found.'];
        }

        $binary = Storage::disk('local')->get($this->temporaryPath);

        try {
            return $ingestion->importManualCv(
                binary: $binary,
                originalFilename: $this->originalFilename,
                mimeType: $this->mimeType,
                fileSize: $this->fileSize,
                context: $this->context,
            );
        } finally {
            Storage::disk('local')->delete($this->temporaryPath);
        }
    }
}
