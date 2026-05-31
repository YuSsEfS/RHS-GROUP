<?php

namespace App\Jobs;

use App\Models\Cv;
use App\Services\CvStorageOptimizationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Schema;

class QueueFailedCvCompressionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 1200;

    public array $backoff = [30, 120, 300];

    public function __construct()
    {
        $this->onConnection('database');
        $this->onQueue('compression');
    }

    public function handle(CvStorageOptimizationService $optimization): void
    {
        if (
            !Schema::hasTable('cvs')
            || !Schema::hasColumn('cvs', 'compression_status')
            || !Schema::hasColumn('cvs', 'compression_verified_at')
        ) {
            return;
        }

        Cv::query()
            ->when(
                Schema::hasColumn('cvs', 'archived_at'),
                fn ($query) => $query->whereNull('archived_at')
            )
            ->where('compression_status', Cv::COMPRESSION_STATUS_FAILED)
            ->whereNull('compression_verified_at')
            ->where(function ($query) {
                $query->whereNull('compression_error')
                    ->orWhere('compression_error', 'not like', '%introuvable%');
            })
            ->select(['id', 'compression_status'])
            ->chunkById(500, function ($cvs) use ($optimization) {
                foreach ($cvs as $cv) {
                    $optimization->markQueued($cv);
                    CompressCvFileJob::dispatch($cv->id);
                }
            });
    }
}
