<?php

namespace App\Jobs;

use App\Models\Cv;
use App\Services\CvStorageOptimizationService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CompressCvFileJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    public int $timeout = 600;

    public array $backoff = [15, 60, 180];

    public int $uniqueFor = 3600;

    public function __construct(public int $cvId)
    {
        $this->onConnection('database');
        $this->onQueue('compression');
    }

    public function uniqueId(): string
    {
        return (string) $this->cvId;
    }

    public function handle(CvStorageOptimizationService $optimization): void
    {
        $cv = Cv::find($this->cvId);

        if (!$cv) {
            return;
        }

        $optimization->markRunning($cv);
        $optimization->compress($cv->fresh() ?? $cv);
    }

    public function failed(\Throwable $e): void
    {
        $cv = Cv::find($this->cvId);

        if ($cv) {
            app(CvStorageOptimizationService::class)->markFailed($cv, $e);
        }
    }
}
