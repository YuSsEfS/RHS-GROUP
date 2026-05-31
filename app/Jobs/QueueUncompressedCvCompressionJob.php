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

class QueueUncompressedCvCompressionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

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
            ->when(Schema::hasColumn('cvs', 'archived_at'), fn ($query) => $query->whereNull('archived_at'))
            ->whereNull('compression_verified_at')
            ->where(function ($query) {
                $query->whereNull('compression_status')
                    ->orWhere('compression_status', Cv::COMPRESSION_STATUS_PENDING)
                    ->orWhere(function ($stale) {
                        $stale->where('compression_status', Cv::COMPRESSION_STATUS_PROCESSING)
                            ->where('updated_at', '<', now()->subMinutes(30));
                    })
                    ->orWhereNotIn('compression_status', [
                        Cv::COMPRESSION_STATUS_PROCESSING,
                        Cv::COMPRESSION_STATUS_COMPLETED,
                        Cv::COMPRESSION_STATUS_FAILED,
                    ]);
            })
            ->select(['id', 'compression_status'])
            ->chunkById(500, function ($cvs) use ($optimization) {
                foreach ($cvs as $cv) {
                    $optimization->markQueued($cv);
                    CompressCvFileJob::dispatch($cv->id);
                }
            });

        $optimization->pruneVerifiedOriginals(1000);
    }
}
