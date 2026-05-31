<?php

namespace App\Http\Controllers\Admin;

use App\Jobs\IndexExternalCvBatchJob;
use App\Http\Controllers\Controller;
use App\Models\Cv;
use App\Models\CvFolder;
use App\Models\ExternalCv;
use App\Models\ExternalCvBatch;
use App\Services\CvStorageOptimizationService;
use App\Services\ProcessingEtaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExternalCvController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', 'all'));

        $batches = ExternalCvBatch::query()
            ->withCount('cvs')
            ->with(['creator', 'folder'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('notes', 'like', "%{$q}%");
                });
            })
            ->when($status !== '' && $status !== 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.external-cvs.index', compact('batches', 'q', 'status'));
    }

    public function create()
    {
        $folders = CvFolder::query()->orderBy('name')->get();

        return view('admin.external-cvs.create', compact('folders'));
    }

    public function store(Request $request)
    {
        @set_time_limit(300);
        @ini_set('memory_limit', '1024M');

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'cv_folder_id' => ['nullable', 'exists:cv_folders,id'],
            'cv_files' => ['required', 'array', 'min:1'],
            'cv_files.*' => ['required', 'file', 'extensions:pdf,doc,docx,txt', 'max:51200'],
            'batch_id' => ['nullable', 'integer', 'exists:external_cv_batches,id'],
            'chunk_index' => ['nullable', 'integer', 'min:0'],
            'total_chunks' => ['nullable', 'integer', 'min:1'],
            'total_files' => ['nullable', 'integer', 'min:1'],
        ]);

        $isAjax = $request->expectsJson() || $request->ajax();
        $chunkIndex = (int) ($validated['chunk_index'] ?? 0);
        $totalChunks = max(1, (int) ($validated['total_chunks'] ?? 1));
        $isLastChunk = ($chunkIndex + 1) >= $totalChunks;

        if (!empty($validated['batch_id'])) {
            $batch = ExternalCvBatch::query()->findOrFail((int) $validated['batch_id']);
            $folderId = $batch->cv_folder_id;
        } else {
            $name = trim((string) ($validated['name'] ?? ''));

            if ($name === '') {
                $name = 'Lot CV ' . now()->format('Y-m-d H-i-s');
            }

            $folderId = $validated['cv_folder_id'] ?? null;

            if (!$folderId) {
                $folderName = $name ?: ('Lot CV ' . now()->format('Y-m-d H-i-s'));
                $slug = Str::slug($folderName);

                $folder = CvFolder::query()->firstOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $folderName,
                        'description' => 'Dossier cree automatiquement depuis un lot externe.',
                        'created_by' => auth()->id(),
                    ]
                );

                $folderId = $folder->id;
            }

            $batch = ExternalCvBatch::create([
                'name' => $name,
                'notes' => $validated['notes'] ?? null,
                'cv_folder_id' => $folderId,
                'total_files' => (int) ($validated['total_files'] ?? count($request->file('cv_files', []))),
                'indexed_files' => 0,
                'failed_files' => 0,
                'duplicate_files' => 0,
                'status' => ExternalCvBatch::STATUS_PENDING,
                'processing_status' => ExternalCvBatch::PROCESSING_STATUS_PENDING,
                'created_by' => auth()->id(),
            ]);
        }

        $storedCount = 0;
        $failedCount = 0;

        foreach ($request->file('cv_files', []) as $file) {
            try {
                $storedPath = $file->store('private/external-cvs/' . $batch->id, 'local');

                try {
                    $hash = hash_file('sha256', $file->getRealPath()) ?: null;
                } catch (\Throwable $e) {
                    $hash = null;
                }

                ExternalCv::create([
                    'batch_id' => $batch->id,
                    'cv_id' => null,
                    'candidate_name' => null,
                    'email' => null,
                    'phone' => null,
                    'city' => null,
                    'current_title' => null,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'stored_path' => $storedPath,
                    'file_hash' => $hash,
                    'extracted_text' => null,
                    'structured_profile' => null,
                    'status' => 'pending',
                    'error_message' => null,
                    'indexed_at' => null,
                ]);

                $storedCount++;
            } catch (\Throwable $e) {
                $failedCount++;
                report($e);
            }
        }

        $realTotal = ExternalCv::query()
            ->where('batch_id', $batch->id)
            ->count();

        $batch->update([
            'total_files' => max((int) $batch->total_files, (int) ($validated['total_files'] ?? $realTotal), $realTotal),
            'failed_files' => (int) $batch->failed_files + $failedCount,
        ]);

        if ($isLastChunk) {
            $batch->update([
                'status' => ExternalCvBatch::STATUS_PROCESSING,
                'processing_status' => ExternalCvBatch::PROCESSING_STATUS_PENDING,
                'processing_started_at' => null,
                'processing_completed_at' => null,
                'processing_error_message' => null,
            ]);

            IndexExternalCvBatchJob::dispatch($batch->id)->afterCommit();
        }

        if ($isAjax) {
            return response()->json([
                'success' => true,
                'batch_id' => $batch->id,
                'stored' => $storedCount,
                'failed' => $failedCount,
                'current_total' => $realTotal,
                'redirect_url' => route('admin.external-cvs.show', $batch),
                'status_url' => route('admin.external-cvs.status', $batch),
                'message' => $isLastChunk
                    ? 'Upload termine. L indexation du lot a ete planifiee en arriere-plan.'
                    : $storedCount . ' CV ajoute(s) au lot.',
            ]);
        }

        return redirect()
            ->route('admin.external-cvs.show', $batch)
            ->with('success', $isLastChunk
                ? 'Lot importe avec succes. L indexation du lot a ete planifiee en arriere-plan.'
                : 'Lot importe avec succes. Dossier CV Bank affecte automatiquement.');
    }

    public function show(ExternalCvBatch $externalCvBatch, Request $request)
    {
        $externalCvBatch->load(['creator', 'folder']);
        $externalCvBatch->refresh();

        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', 'all'));

        $files = ExternalCv::query()
            ->where('batch_id', $externalCvBatch->id)
            ->with('cv')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('original_filename', 'like', "%{$q}%")
                        ->orWhere('candidate_name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('city', 'like', "%{$q}%")
                        ->orWhere('current_title', 'like', "%{$q}%");
                });
            })
            ->when($status !== '' && $status !== 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('admin.external-cvs.show', [
            'batch' => $externalCvBatch,
            'files' => $files,
            'q' => $q,
            'status' => $status,
        ]);
    }

    public function indexBatch(Request $request, ExternalCvBatch $externalCvBatch)
    {
        try {
            $forceReindex = (bool) $request->boolean('force_reindex');

            $externalCvBatch->update([
                'status' => ExternalCvBatch::STATUS_PROCESSING,
                'processing_status' => ExternalCvBatch::PROCESSING_STATUS_PENDING,
                'processing_started_at' => null,
                'processing_completed_at' => null,
                'processing_error_message' => null,
            ]);

            IndexExternalCvBatchJob::dispatch($externalCvBatch->id, $forceReindex)->afterCommit();

            return redirect()
                ->route('admin.external-cvs.show', $externalCvBatch)
                ->with('success', $forceReindex
                    ? 'La reindexation du lot a ete planifiee en arriere-plan.'
                    : 'L indexation du lot a ete planifiee en arriere-plan.');
        } catch (\Throwable $e) {
            $externalCvBatch->update([
                'status' => ExternalCvBatch::STATUS_FAILED,
                'processing_status' => ExternalCvBatch::PROCESSING_STATUS_FAILED,
                'processing_completed_at' => now(),
                'processing_error_message' => mb_substr($e->getMessage(), 0, 2000),
            ]);

            return redirect()
                ->route('admin.external-cvs.show', $externalCvBatch)
                ->with('error', 'Erreur lors de l indexation du lot : ' . $e->getMessage());
        }
    }

    public function status(ExternalCvBatch $externalCvBatch, ProcessingEtaService $eta)
    {
        $totals = ExternalCv::query()
            ->where('batch_id', $externalCvBatch->id)
            ->selectRaw("
                COUNT(*) as total_files,
                SUM(CASE WHEN status = 'indexed' THEN 1 ELSE 0 END) as indexed_files,
                SUM(CASE WHEN status = 'duplicate' THEN 1 ELSE 0 END) as duplicate_files,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_files,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_files
            ")
            ->first();

        $totalFiles = max((int) ($externalCvBatch->total_files ?? 0), (int) ($totals->total_files ?? 0));
        $indexedFiles = (int) ($totals->indexed_files ?? 0);
        $duplicateFiles = (int) ($totals->duplicate_files ?? 0);
        $failedFiles = (int) ($totals->failed_files ?? 0);
        $pendingFiles = max(0, $totalFiles - ($indexedFiles + $duplicateFiles + $failedFiles));
        $progressPercentage = $totalFiles > 0
            ? (int) round((($indexedFiles + $duplicateFiles + $failedFiles) / $totalFiles) * 100)
            : 0;
        $processedItems = $indexedFiles + $duplicateFiles + $failedFiles;
        $recent = $this->recentIndexingThroughput($externalCvBatch);
        $queuedJobs = $this->queuedIndexingJobsCount($externalCvBatch);
        $etaPayload = $eta->payload(
            processed: $processedItems,
            total: $totalFiles,
            startedAt: $externalCvBatch->processing_started_at ?: $externalCvBatch->created_at,
            status: $externalCvBatch->processing_status ?: ExternalCvBatch::PROCESSING_STATUS_PENDING,
            recentProcessed: $recent['processed'],
            recentWindowSeconds: $recent['window_seconds'],
            preferRecent: true
        );

        return response()->json([
            'status' => $externalCvBatch->processing_status ?: ExternalCvBatch::PROCESSING_STATUS_PENDING,
            'total_files' => $totalFiles,
            'indexed_files' => $indexedFiles,
            'duplicate_files' => $duplicateFiles,
            'failed_files' => $failedFiles,
            'pending_files' => $pendingFiles,
            'queued_jobs' => $queuedJobs,
            'progress_percentage' => $progressPercentage,
            'error_message' => $externalCvBatch->processing_error_message,
            'status_message' => match (true) {
                $pendingFiles <= 0 && $failedFiles > 0 => 'Le traitement du lot est termine avec des echecs. Filtrez sur Echec pour voir les fichiers a corriger ou relancer.',
                $pendingFiles <= 0 => 'Le traitement du lot est termine.',
                $queuedJobs > 0 => 'Indexation planifiee dans la file. Lancez le worker indexing si le compteur ne bouge pas.',
                $recent['processed'] > 0 => 'Indexation synchronisee avec le worker.',
                default => 'Aucun job actif detecte pour ce lot. Cliquez sur Reprendre l indexation si le compteur reste bloque.',
            },
        ] + $etaPayload);
    }

    private function recentIndexingThroughput(ExternalCvBatch $externalCvBatch): array
    {
        $minutes = 10;
        $since = now()->subMinutes($minutes);
        $query = ExternalCv::query()
            ->where('batch_id', $externalCvBatch->id)
            ->whereIn('status', [
                ExternalCv::STATUS_INDEXED,
                ExternalCv::STATUS_DUPLICATE,
                ExternalCv::STATUS_FAILED,
            ])
            ->where('updated_at', '>=', $since);

        $processed = (int) (clone $query)->count();
        $firstRecent = (clone $query)->min('updated_at');
        $windowSeconds = $firstRecent
            ? max(60, \Illuminate\Support\Carbon::parse($firstRecent)->diffInSeconds(now(), true))
            : $minutes * 60;

        return [
            'processed' => $processed,
            'window_seconds' => $windowSeconds,
        ];
    }

    private function queuedIndexingJobsCount(ExternalCvBatch $externalCvBatch): int
    {
        if (!Schema::hasTable('jobs')) {
            return 0;
        }

        return DB::table('jobs')
            ->whereIn('queue', ['indexing', 'external-indexing'])
            ->where('payload', 'like', '%IndexExternalCvBatchJob%')
            ->where('payload', 'like', '%' . $externalCvBatch->id . '%')
            ->count();
    }

    public function destroy(Request $request, ExternalCvBatch $externalCvBatch)
    {
        $validated = $request->validate([
            'delete_mode' => ['required', 'in:batch_only,batch_and_cvs'],
        ]);

        DB::transaction(function () use ($validated, $externalCvBatch) {
            $storageOptimization = app(CvStorageOptimizationService::class);
            $files = ExternalCv::query()
                ->where('batch_id', $externalCvBatch->id)
                ->get();

            foreach ($files as $file) {
                $linkedCv = $file->cv_id ? Cv::find($file->cv_id) : null;

                if (!$linkedCv) {
                    continue;
                }

                if (
                    $validated['delete_mode'] === 'batch_and_cvs'
                    && $this->canSafelyDeleteLinkedCv($linkedCv, $file)
                ) {
                    $storageOptimization->deleteStoredFiles($linkedCv);
                    $linkedCv->delete();
                    continue;
                }

                $this->detachLinkedCvFromExternalSource($linkedCv, $file, $storageOptimization);
            }

            foreach ($files as $file) {
                $isStillReferenced = !empty($file->stored_path)
                    && Cv::query()->where('encrypted_path', $file->stored_path)->exists();

                if (!$isStillReferenced && !empty($file->stored_path) && Storage::disk('local')->exists($file->stored_path)) {
                    Storage::disk('local')->delete($file->stored_path);
                }

                $file->delete();
            }

            $externalCvBatch->delete();
        });

        return redirect()
            ->route('admin.external-cvs.index')
            ->with('success', 'Dossier d indexation supprime avec succes.');
    }

    public function open(ExternalCv $externalCv)
    {
        abort_unless(
            !empty($externalCv->stored_path) && Storage::disk('local')->exists($externalCv->stored_path),
            404,
            'Fichier introuvable.'
        );

        $fullPath = Storage::disk('local')->path($externalCv->stored_path);
        $filename = $externalCv->original_filename ?: ('external-cv-' . $externalCv->id);
        $mime = $externalCv->mime_type ?: 'application/octet-stream';

        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . addslashes($filename) . '"',
        ]);
    }

    private function canSafelyDeleteLinkedCv(Cv $cv, ExternalCv $externalCv): bool
    {
        if (!Schema::hasColumn('cvs', 'source_type') || !Schema::hasColumn('cvs', 'source_id')) {
            return false;
        }

        if ($cv->source_type !== 'external_db' || (int) $cv->source_id !== (int) $externalCv->id) {
            return false;
        }

        return !ExternalCv::query()
            ->where('cv_id', $cv->id)
            ->whereKeyNot($externalCv->id)
            ->exists();
    }

    private function detachLinkedCvFromExternalSource(
        Cv $cv,
        ExternalCv $externalCv,
        CvStorageOptimizationService $storageOptimization
    ): void {
        if (
            !Schema::hasColumn('cvs', 'source_type')
            || !Schema::hasColumn('cvs', 'source_id')
        ) {
            return;
        }

        $updates = [];

        if ($cv->source_type === 'external_db' && (int) $cv->source_id === (int) $externalCv->id) {
            if ($cv->encrypted_path === $externalCv->stored_path) {
                $storageOptimization->preserveExternalFileForCv(
                    $cv,
                    (string) $externalCv->stored_path,
                    (string) $externalCv->original_filename
                );
            }

            $updates['source_type'] = null;
            $updates['source_id'] = null;
        }

        if (!empty($updates)) {
            $cv->update($updates);
        }
    }
}
