<?php

namespace App\Jobs;

use App\Http\Controllers\Admin\OdooPreselectionController;
use App\Models\RecruitmentRequest;
use App\Services\CvStorageOptimizationService;
use App\Services\OdooService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExportSelectedCvsToOdooJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 1800;

    public array $backoff = [60, 180, 600];

    public function __construct(
        public int $recruitmentRequestId,
        public array $selectedMatchIds,
    ) {
        $this->onConnection('database');
        $this->onQueue('odoo');
    }

    public function handle(
        OdooPreselectionController $controller,
        OdooService $odoo,
        CvStorageOptimizationService $storageOptimization
    ): void {
        $recruitmentRequest = RecruitmentRequest::find($this->recruitmentRequestId);

        if (!$recruitmentRequest || empty($this->selectedMatchIds)) {
            OdooPreselectionController::putExportStatus($this->recruitmentRequestId, [
                'status' => 'failed',
                'message' => 'Export Odoo impossible: demande ou selection introuvable.',
                'selected_count' => count($this->selectedMatchIds),
            ]);
            return;
        }

        OdooPreselectionController::putExportStatus($this->recruitmentRequestId, [
            'status' => 'running',
            'message' => count($this->selectedMatchIds) . ' CV en cours d envoi vers Odoo.',
            'selected_count' => count($this->selectedMatchIds),
        ]);

        $result = $controller->processQueuedExport(
            $recruitmentRequest,
            $this->selectedMatchIds,
            $odoo,
            $storageOptimization
        );

        $resultStatus = (string) ($result['status'] ?? 'failed');
        $uiStatus = match ($resultStatus) {
            'success' => 'success',
            'skipped' => 'warning',
            default => 'failed',
        };

        OdooPreselectionController::putExportStatus($this->recruitmentRequestId, array_merge($result, [
            'status' => $uiStatus,
            'selected_count' => count($this->selectedMatchIds),
        ]));

        Log::info('Odoo queued CV export finished', [
            'request_id' => $this->recruitmentRequestId,
            'selected_match_ids' => $this->selectedMatchIds,
            'result' => $result,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        OdooPreselectionController::putExportStatus($this->recruitmentRequestId, [
            'status' => 'failed',
            'message' => 'Erreur export Odoo: ' . $e->getMessage(),
            'selected_count' => count($this->selectedMatchIds),
        ]);

        Log::error('Odoo queued CV export failed', [
            'request_id' => $this->recruitmentRequestId,
            'selected_match_ids' => $this->selectedMatchIds,
            'message' => $e->getMessage(),
        ]);
    }
}
