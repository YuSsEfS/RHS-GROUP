<?php

namespace App\Http\Controllers\Admin;

use App\Jobs\CompressCvFileJob;
use App\Jobs\QueueFailedCvCompressionJob;
use App\Jobs\QueueUncompressedCvCompressionJob;
use App\Jobs\ProcessManualCvUploadJob;
use App\Http\Controllers\Controller;
use App\Models\Cv;
use App\Models\CvFolder;
use App\Models\CvImportBatch;
use App\Models\JobApplication;
use App\Models\JobOffer;
use App\Services\CvStorageOptimizationService;
use App\Services\ProcessingEtaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CvController extends Controller
{
    public function index(Request $request)
    {
        return $this->renderIndex($request, false);
    }

    public function archived(Request $request)
    {
        return $this->renderIndex($request, true);
    }

    private function renderIndex(Request $request, bool $archived)
    {
        $filters = $this->cvFiltersFromRequest($request, $archived);
        $q = $filters['q'];
        $source = $filters['source'];
        $folder = $filters['folder'];
        $status = $filters['status'];
        $offer = $filters['offer'];

        $direction = trim((string) $request->query('direction', 'desc'));

        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        $query = Cv::query()->select($this->cvIndexColumns());

        if (method_exists(Cv::class, 'folder')) {
            $query->with('folder:id,name');
        }

        $this->applyCvFilters($query, $filters);

        if (Schema::hasColumn('cvs', 'uploaded_at')) {
            $query->orderBy('uploaded_at', $direction)->orderBy('id', 'desc');
        } else {
            $query->orderBy('id', $direction);
        }

        $hasActiveFilters = $q !== ''
            || !in_array($source, ['', 'all'], true)
            || !in_array($folder, ['', 'all'], true)
            || !in_array($offer, ['', 'all'], true)
            || !in_array($status, [$archived ? 'archived' : 'active', 'all'], true);

        // The CV Bank can contain tens of thousands of rows. A full paginator
        // runs costly COUNT(*) queries, so listings use the lightweight variant.
        $cvs = $query->simplePaginate(30)->withQueryString();

        $cvListTotal = $hasActiveFilters
            ? $cvs->count() . '+'
            : Cache::remember('admin.cvs.' . ($archived ? 'archived' : 'active') . '.count.v1', now()->addSeconds(60), function () use ($archived) {
                return Cv::query()
                    ->when(
                        Schema::hasColumn('cvs', 'archived_at') && $archived,
                        fn ($builder) => $builder->whereNotNull('archived_at')
                    )
                    ->when(
                        Schema::hasColumn('cvs', 'archived_at') && !$archived,
                        fn ($builder) => $builder->whereNull('archived_at')
                    )
                    ->when(
                        Schema::hasColumn('cvs', 'is_active') && !$archived,
                        fn ($builder) => $builder->where('is_active', true)
                    )
                    ->count();
            });

        $folders = class_exists(CvFolder::class)
            ? Cache::remember('admin.cv_folders.options.v1', now()->addSeconds(30), fn () => CvFolder::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get())
            : collect();

        $offers = Cache::remember('admin.job_offers.options.v1', now()->addSeconds(30), fn () => JobOffer::query()
            ->select('id', 'title')
            ->orderBy('title')
            ->get());

        $storageStats = $this->lightweightStorageStats($cvListTotal);

        return view('admin.cvs.index', compact(
            'cvs',
            'folders',
            'offers',
            'storageStats',
            'cvListTotal',
            'q',
            'source',
            'folder',
            'status',
            'offer',
            'direction',
            'archived'
        ));
    }

    public function create(Request $request)
    {
        $folders = class_exists(CvFolder::class)
            ? CvFolder::query()->orderBy('name')->get()
            : collect();

        $importBatch = $request->filled('import_batch')
            ? CvImportBatch::query()->find((int) $request->query('import_batch'))
            : null;

        return view('admin.cvs.create', compact('folders', 'importBatch'));
    }

    public function store(Request $request)
    {
        $rules = [
            'cv_files' => ['required', 'array', 'min:1'],
            'cv_files.*' => ['required', 'file', 'mimes:pdf,doc,docx,txt', 'max:51200'],
            'relative_paths' => ['nullable', 'array'],
            'relative_paths.*' => ['nullable', 'string', 'max:1000'],
            'new_folder_name' => ['nullable', 'string', 'max:255'],
            'batch_id' => ['nullable', 'integer', 'exists:cv_import_batches,id'],
            'chunk_index' => ['nullable', 'integer', 'min:0'],
            'total_chunks' => ['nullable', 'integer', 'min:1'],
            'total_files' => ['nullable', 'integer', 'min:1'],
        ];

        if (Schema::hasColumn('cvs', 'cv_folder_id')) {
            $rules['cv_folder_id'] = ['nullable', 'integer', 'exists:cv_folders,id'];
        }

        if (Schema::hasColumn('cvs', 'city')) {
            $rules['city'] = ['nullable', 'string', 'max:255'];
        }

        if (Schema::hasColumn('cvs', 'current_title')) {
            $rules['current_title'] = ['nullable', 'string', 'max:255'];
        }

        if (Schema::hasColumn('cvs', 'notes')) {
            $rules['notes'] = ['nullable', 'string'];
        }

        $validated = $request->validate($rules);

        $targetFolderId = $this->resolveTargetFolderId($request);
        $isAsync = $request->expectsJson() || $request->ajax();
        $declaredTotalFiles = (int) ($validated['total_files'] ?? count($request->file('cv_files', [])));

        $batch = !empty($validated['batch_id'])
            ? CvImportBatch::query()->findOrFail((int) $validated['batch_id'])
            : CvImportBatch::create([
                'name' => 'Import CV ' . now()->format('Y-m-d H:i:s'),
                'cv_folder_id' => $targetFolderId,
                'total_files' => max(1, $declaredTotalFiles),
                'queued_files' => 0,
                'processed_files' => 0,
                'failed_files' => 0,
                'duplicate_files' => 0,
                'status' => CvImportBatch::STATUS_PENDING,
                'created_by' => auth()->id(),
            ]);

        $queuedCount = 0;
        $uploadedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        foreach ($request->file('cv_files', []) as $file) {
            try {
                $extension = strtolower($file->getClientOriginalExtension());
                $tempPath = 'temp/manual-cv-imports/' . uniqid('cv_', true) . '.' . $extension;

                Storage::disk('local')->put($tempPath, file_get_contents($file->getRealPath()));

                ProcessManualCvUploadJob::dispatch(
                    temporaryPath: $tempPath,
                    originalFilename: $file->getClientOriginalName(),
                    mimeType: $file->getMimeType(),
                    fileSize: (int) $file->getSize(),
                    context: [
                        'cv_folder_id' => $targetFolderId,
                        'city' => $validated['city'] ?? null,
                        'current_title' => $validated['current_title'] ?? null,
                        'notes' => $validated['notes'] ?? null,
                    ],
                    importBatchId: $batch->id,
                )->afterCommit();

                $queuedCount++;
                $uploadedCount++;
            } catch (\Throwable $e) {
                $failedCount++;
                report($e);
            }
        }

        $batch->forceFill([
            'cv_folder_id' => $batch->cv_folder_id ?: $targetFolderId,
            'total_files' => max((int) $batch->total_files, max(1, $declaredTotalFiles)),
            'queued_files' => (int) $batch->queued_files + $queuedCount,
            'failed_files' => (int) $batch->failed_files + $failedCount,
            'status' => ($queuedCount > 0 || $failedCount > 0)
                ? CvImportBatch::STATUS_PROCESSING
                : $batch->status,
            'started_at' => ($queuedCount > 0 || $failedCount > 0)
                ? ($batch->started_at ?: now())
                : $batch->started_at,
        ])->save();

        $batch->refresh();
        $batch->refreshProgressState();

        $message = "{$queuedCount} CV(s) envoye(s) pour indexation en arriere-plan.";

        if ($skippedCount > 0) {
            $message .= " {$skippedCount} fichier(s) en double ignore(s).";
        }

        if ($failedCount > 0) {
            $message .= " {$failedCount} fichier(s) ont echoue pendant l upload.";
        }

        if ($isAsync) {
            return response()->json([
                'success' => true,
                'batch_id' => $batch->id,
                'uploaded' => $uploadedCount,
                'queued' => $queuedCount,
                'skipped' => $skippedCount,
                'failed' => $failedCount,
                'status_url' => route('admin.cvs.import-status', $batch),
                'progress_percentage' => $batch->progressPercentage(),
                'message' => $message,
            ]);
        }

        return redirect()
            ->route('admin.cvs.create', [
                'import_batch' => $batch->id,
            ])
            ->with('success', $message);
    }

    public function importStatus(CvImportBatch $cvImportBatch, ProcessingEtaService $eta)
    {
        $cvImportBatch->refreshProgressState();
        $cvImportBatch->refresh();
        $processedItems = (int) $cvImportBatch->processed_files
            + (int) $cvImportBatch->failed_files
            + (int) $cvImportBatch->duplicate_files;
        $etaPayload = $eta->payload(
            processed: $processedItems,
            total: (int) $cvImportBatch->total_files,
            startedAt: $cvImportBatch->started_at ?: $cvImportBatch->created_at,
            status: $cvImportBatch->status
        );

        return response()->json([
            'status' => $cvImportBatch->status,
            'total_files' => (int) $cvImportBatch->total_files,
            'queued_files' => (int) $cvImportBatch->queued_files,
            'processed_files' => (int) $cvImportBatch->processed_files,
            'failed_files' => (int) $cvImportBatch->failed_files,
            'duplicate_files' => (int) $cvImportBatch->duplicate_files,
            'pending_files' => $cvImportBatch->pendingFilesCount(),
            'progress_percentage' => $cvImportBatch->progressPercentage(),
            'error_message' => $cvImportBatch->error_message,
        ] + $etaPayload);
    }

    public function storageOptimizationStatus(Request $request)
    {
        $archived = $request->boolean('archived', false);
        $filters = $this->cvFiltersFromRequest($request, $archived);
        $query = Cv::query();
        $this->applyCvFilters($query, $filters);

        $stats = $this->filteredStorageStats($query);

        return response()->json($stats);
    }

    public function open(Cv $cv, CvStorageOptimizationService $storageOptimization)
    {
        $mime = $cv->mime_type ?: $this->guessMimeTypeFromExtension(pathinfo((string) $cv->original_filename, PATHINFO_EXTENSION));

        if (str_starts_with($mime, 'application/pdf') || str_starts_with($mime, 'image/')) {
            return view('admin.cvs.viewer', [
                'cv' => $cv,
                'streamUrl' => route('admin.cvs.stream', $cv),
                'mime' => $mime,
            ]);
        }

        return $this->stream($cv, $storageOptimization);
    }

    public function stream(Cv $cv, CvStorageOptimizationService $storageOptimization)
    {
        if (!empty($cv->encrypted_path) && Storage::disk('local')->exists($cv->encrypted_path)) {
            $fullPath = Storage::disk('local')->path($cv->encrypted_path);
            $filename = $cv->original_filename ?: ('cv-' . $cv->id);
            $mime = $cv->mime_type ?: 'application/octet-stream';

            return response()->file($fullPath, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . addslashes($filename) . '"',
            ]);
        }

        if (
            Schema::hasColumn('cvs', 'source_type') &&
            Schema::hasColumn('cvs', 'source_id') &&
            $cv->source_type === 'application' &&
            !empty($cv->source_id)
        ) {
            $application = JobApplication::find($cv->source_id);

            if ($application && !empty($application->cv_path)) {
                $relativePath = ltrim($application->cv_path, '/');

                if (Storage::disk('public')->exists($relativePath)) {
                    $fullPath = Storage::disk('public')->path($relativePath);
                    $filename = basename($relativePath);
                    $mime = $cv->mime_type ?: $this->guessMimeTypeFromExtension(pathinfo($relativePath, PATHINFO_EXTENSION));

                    return response()->file($fullPath, [
                        'Content-Type' => $mime,
                        'Content-Disposition' => 'inline; filename="' . addslashes($filename) . '"',
                    ]);
                }
            }
        }

        $binary = $storageOptimization->readBinary($cv);

        if ($binary !== null) {
            $filename = $cv->original_filename ?: ('cv-' . $cv->id);
            $mime = $cv->mime_type ?: 'application/octet-stream';

            return response($binary, 200, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . addslashes($filename) . '"',
            ]);
        }

        abort(404, 'CV file not found.');
    }

    public function assignFolder(Request $request, Cv $cv)
    {
        $validated = $request->validate([
            'cv_folder_id' => ['nullable', 'integer', 'exists:cv_folders,id'],
        ]);

        if (Schema::hasColumn('cvs', 'cv_folder_id')) {
            $cv->cv_folder_id = $validated['cv_folder_id'] ?? null;
            $cv->save();
        }

        return redirect()
            ->route('admin.cvs.index')
            ->with('success', 'Dossier du CV mis a jour avec succes.');
    }

    public function destroy(Cv $cv, CvStorageOptimizationService $storageOptimization)
    {
        $storageOptimization->deleteStoredFiles($cv);

        $cv->delete();

        return redirect()
            ->route('admin.cvs.index')
            ->with('success', 'CV supprime avec succes.');
    }

    public function bulkDestroy(Request $request, CvStorageOptimizationService $storageOptimization)
    {
        $validated = $request->validate([
            'cv_ids' => ['required', 'array', 'min:1'],
            'cv_ids.*' => ['integer', 'exists:cvs,id'],
        ]);

        $cvs = Cv::whereIn('id', $validated['cv_ids'])->get();

        foreach ($cvs as $cv) {
            $storageOptimization->deleteStoredFiles($cv);
            $cv->delete();
        }

        return redirect()
            ->route('admin.cvs.index')
            ->with('success', $cvs->count() . ' CV supprime(s) avec succes.');
    }

    public function archive(Cv $cv)
    {
        if (Schema::hasColumn('cvs', 'archived_at')) {
            $cv->forceFill([
                'archived_at' => now(),
                'archived_by' => auth()->id(),
                'archive_reason' => 'Archivage manuel',
                'is_active' => false,
            ])->save();
        }

        return redirect()
            ->route('admin.cvs.index')
            ->with('success', 'Le CV a ete archive avec succes.');
    }

    public function restore(Cv $cv)
    {
        if (Schema::hasColumn('cvs', 'archived_at')) {
            $cv->forceFill([
                'archived_at' => null,
                'archived_by' => null,
                'archive_reason' => null,
                'is_active' => true,
            ])->save();
        }

        return redirect()
            ->route('admin.cvs.archived')
            ->with('success', 'Le CV a ete restaure dans la CV Bank.');
    }

    public function bulkArchive(Request $request)
    {
        $validated = $request->validate([
            'cv_ids' => ['required', 'array', 'min:1'],
            'cv_ids.*' => ['integer', 'exists:cvs,id'],
        ]);

        $updatedCount = 0;

        if (Schema::hasColumn('cvs', 'archived_at')) {
            $updatedCount = Cv::query()
                ->whereIn('id', $validated['cv_ids'])
                ->update([
                    'archived_at' => now(),
                    'archived_by' => auth()->id(),
                    'archive_reason' => 'Archivage en lot',
                    'is_active' => false,
                ]);
        }

        return redirect()
            ->route('admin.cvs.index')
            ->with('success', $updatedCount . ' CV archive(s) avec succes.');
    }

    public function bulkAssignFolder(Request $request)
    {
        $validated = $request->validate([
            'cv_ids' => ['required', 'array', 'min:1'],
            'cv_ids.*' => ['integer', 'exists:cvs,id'],
            'cv_folder_id' => ['nullable', 'integer', 'exists:cv_folders,id'],
        ]);

        if (!Schema::hasColumn('cvs', 'cv_folder_id')) {
            return redirect()
                ->route('admin.cvs.index')
                ->with('error', 'L assignation par dossier n est pas disponible sur cette installation.');
        }

        $targetFolderId = $validated['cv_folder_id'] ?? null;
        $updatedCount = Cv::query()
            ->whereIn('id', $validated['cv_ids'])
            ->update([
                'cv_folder_id' => $targetFolderId,
            ]);

        $message = $targetFolderId
            ? $updatedCount . ' CV assigne(s) au dossier avec succes.'
            : $updatedCount . ' CV desassigne(s) du dossier avec succes.';

        return redirect()
            ->route('admin.cvs.index')
            ->with('success', $message);
    }

    public function optimizeStorage(Cv $cv, CvStorageOptimizationService $storageOptimization)
    {
        if (!config('cv_storage.enable_compression', true)) {
            return redirect()
                ->route('admin.cvs.index')
                ->with('error', 'L optimisation de stockage est desactivee.');
        }

        $storageOptimization->markQueued($cv);

        CompressCvFileJob::dispatch($cv->id)->afterCommit();

        return redirect()
            ->route('admin.cvs.index')
            ->with('success', 'L optimisation de stockage du CV a ete planifiee en arriere-plan.');
    }

    public function bulkOptimizeStorage(Request $request, CvStorageOptimizationService $storageOptimization)
    {
        if (!config('cv_storage.enable_compression', true)) {
            return redirect()
                ->route('admin.cvs.index')
                ->with('error', 'L optimisation de stockage est desactivee.');
        }

        $validated = $request->validate([
            'cv_ids' => ['required', 'array', 'min:1'],
            'cv_ids.*' => ['integer', 'exists:cvs,id'],
        ]);

        $cvs = Cv::query()
            ->whereIn('id', $validated['cv_ids'])
            ->get();

        foreach ($cvs as $cv) {
            $storageOptimization->markQueued($cv);
            CompressCvFileJob::dispatch($cv->id)->afterCommit();
        }

        return redirect()
            ->route('admin.cvs.index')
            ->with('success', $cvs->count() . ' optimisation(s) de stockage ont ete planifiees.');
    }

    public function optimizeUncompressedStorage()
    {
        if (!config('cv_storage.enable_compression', true)) {
            return redirect()
                ->route('admin.cvs.index')
                ->with('error', 'L optimisation de stockage est desactivee.');
        }

        $pendingCount = Cv::query()
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
            ->count();

        if ($pendingCount === 0) {
            return redirect()
                ->route('admin.cvs.index')
                ->with('success', 'Aucun CV non optimise recuperable a planifier pour le moment.');
        }

        QueueUncompressedCvCompressionJob::dispatch();

        return redirect()
            ->route('admin.cvs.index')
            ->with('success', $pendingCount . ' CV non optimise(s) ont ete planifies. Si le compteur reste en attente, lancez le worker compression.');
    }

    public function retryFailedCompression()
    {
        if (!config('cv_storage.enable_compression', true)) {
            return redirect()
                ->route('admin.cvs.index')
                ->with('error', 'L optimisation de stockage est desactivee.');
        }

        $recoverableFailures = Cv::query()
            ->when(Schema::hasColumn('cvs', 'archived_at'), fn ($query) => $query->whereNull('archived_at'))
            ->where('compression_status', Cv::COMPRESSION_STATUS_FAILED)
            ->whereNull('compression_verified_at')
            ->where(function ($query) {
                $query->whereNull('compression_error')
                    ->orWhere('compression_error', 'not like', '%introuvable%');
            })
            ->count();

        if ($recoverableFailures === 0) {
            return redirect()
                ->route('admin.cvs.index')
                ->with('success', 'Aucune recompression necessaire : les echecs restants concernent des fichiers originaux manquants. Les CV disponibles sont deja traites.');
        }

        QueueFailedCvCompressionJob::dispatch();

        return redirect()
            ->route('admin.cvs.index')
            ->with('success', $recoverableFailures . ' echec(s) recuperable(s) ont ete planifies en arriere-plan.');
    }

    private function resolveTargetFolderId(Request $request): ?int
    {
        if (!class_exists(CvFolder::class)) {
            return null;
        }

        $existingFolderId = $request->filled('cv_folder_id')
            ? (int) $request->input('cv_folder_id')
            : null;

        $newFolderName = trim((string) $request->input('new_folder_name', ''));

        if ($newFolderName !== '') {
            $folder = CvFolder::firstOrCreate(
                ['slug' => Str::slug($newFolderName)],
                [
                    'name' => $newFolderName,
                    'description' => null,
                    'created_by' => optional(auth()->user())->id,
                ]
            );

            return (int) $folder->id;
        }

        if ($existingFolderId) {
            return $existingFolderId;
        }

        $uploadedDirName = $this->extractTopDirectoryNameFromUpload($request);

        if ($uploadedDirName) {
            $folder = CvFolder::firstOrCreate(
                ['slug' => Str::slug($uploadedDirName)],
                [
                    'name' => $uploadedDirName,
                    'description' => null,
                    'created_by' => optional(auth()->user())->id,
                ]
            );

            return (int) $folder->id;
        }

        return null;
    }

    private function cvIndexColumns(): array
    {
        $baseColumns = [
            'id',
            'candidate_name',
            'email',
            'phone',
            'original_filename',
            'file_size',
            'uploaded_at',
            'source_type',
            'source_id',
            'mime_type',
            'encrypted_path',
            'cv_folder_id',
            'current_title',
            'city',
            'is_active',
            'original_file_size',
            'compressed_file_size',
            'compression_status',
            'compression_verified_at',
            'duplicate_of_cv_id',
            'archived_at',
        ];

        return array_values(array_filter(
            $baseColumns,
            fn (string $column) => Schema::hasColumn('cvs', $column)
        ));
    }

    private function cvFiltersFromRequest(Request $request, bool $archived): array
    {
        return [
            'archived' => $archived,
            'q' => trim((string) $request->query('q', '')),
            'source' => trim((string) $request->query('source', 'all')),
            'folder' => trim((string) $request->query('folder', 'all')),
            'status' => trim((string) $request->query('status', $archived ? 'archived' : 'active')),
            'offer' => trim((string) $request->query('offer', 'all')),
        ];
    }

    private function applyCvFilters($query, array $filters): void
    {
        $archived = (bool) ($filters['archived'] ?? false);
        $q = (string) ($filters['q'] ?? '');
        $source = (string) ($filters['source'] ?? 'all');
        $folder = (string) ($filters['folder'] ?? 'all');
        $status = (string) ($filters['status'] ?? ($archived ? 'archived' : 'active'));
        $offer = (string) ($filters['offer'] ?? 'all');

        $query->when(
            Schema::hasColumn('cvs', 'archived_at') && $archived,
            fn ($builder) => $builder->whereNotNull('archived_at')
        )->when(
            Schema::hasColumn('cvs', 'archived_at') && !$archived,
            fn ($builder) => $builder->whereNull('archived_at')
        );

        $query->when($q !== '', function ($builder) use ($q) {
            $builder->where(function ($sub) use ($q) {
                $sub->where('candidate_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('original_filename', 'like', "%{$q}%");

                if (Schema::hasColumn('cvs', 'city')) {
                    $sub->orWhere('city', 'like', "%{$q}%");
                }

                if (Schema::hasColumn('cvs', 'current_title')) {
                    $sub->orWhere('current_title', 'like', "%{$q}%");
                }
            });
        });

        if (Schema::hasColumn('cvs', 'source_type') && $source !== '' && $source !== 'all') {
            $query->where('source_type', $source);
        }

        if (Schema::hasColumn('cvs', 'cv_folder_id') && $folder !== '' && $folder !== 'all') {
            $query->where('cv_folder_id', (int) $folder);
        }

        if (Schema::hasColumn('cvs', 'is_active')) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if (
            $offer !== '' &&
            $offer !== 'all' &&
            Schema::hasColumn('cvs', 'source_type') &&
            Schema::hasColumn('cvs', 'source_id')
        ) {
            $query->where('source_type', 'application');

            if ($offer === 'spontaneous') {
                $query->whereIn('source_id', function ($sub) {
                    $sub->select('id')
                        ->from('job_applications')
                        ->whereNull('job_offer_id');
                });
            } else {
                $offerId = (int) $offer;

                $query->whereIn('source_id', function ($sub) use ($offerId) {
                    $sub->select('id')
                        ->from('job_applications')
                        ->where('job_offer_id', $offerId);
                });
            }
        }
    }

    private function filteredStorageStats($query): array
    {
        $select = 'COUNT(*) as total_files';
        $bindings = [];

        if (Schema::hasColumn('cvs', 'original_file_size')) {
            $select .= ', SUM(COALESCE(original_file_size, file_size, 0)) as total_original_size';
        } else {
            $select .= ', SUM(COALESCE(file_size, 0)) as total_original_size';
        }

        if (
            Schema::hasColumn('cvs', 'compression_status') &&
            Schema::hasColumn('cvs', 'compression_verified_at') &&
            Schema::hasColumn('cvs', 'compressed_file_size')
        ) {
            $select .= ",
                SUM(
                    CASE
                        WHEN compression_status = ? AND compression_verified_at IS NOT NULL AND compressed_file_size IS NOT NULL
                            THEN LEAST(compressed_file_size, COALESCE(original_file_size, file_size, 0))
                        ELSE COALESCE(original_file_size, file_size, 0)
                    END
                ) as total_current_size,
                SUM(
                    CASE
                        WHEN compression_verified_at IS NOT NULL AND compressed_file_size IS NOT NULL
                            THEN GREATEST(COALESCE(original_file_size, file_size, 0) - compressed_file_size, 0)
                        ELSE 0
                    END
                ) as estimated_saved_space,
                SUM(CASE WHEN compression_status = ? AND compression_verified_at IS NOT NULL THEN 1 ELSE 0 END) as completed_files,
                SUM(CASE WHEN compression_status = ? THEN 1 ELSE 0 END) as processing_files,
                SUM(CASE WHEN compression_status = ? THEN 1 ELSE 0 END) as pending_files,
                SUM(CASE WHEN compression_status = ? THEN 1 ELSE 0 END) as failed_files,
                SUM(CASE WHEN compression_status = ? AND compression_error LIKE ? THEN 1 ELSE 0 END) as missing_files,
                SUM(CASE
                    WHEN compression_verified_at IS NULL
                        AND (
                            compression_status IS NULL
                            OR compression_status NOT IN (?, ?, ?)
                        )
                    THEN 1 ELSE 0 END
                ) as unoptimized_files
            ";
            $bindings = [
                Cv::COMPRESSION_STATUS_COMPLETED,
                Cv::COMPRESSION_STATUS_COMPLETED,
                Cv::COMPRESSION_STATUS_PROCESSING,
                Cv::COMPRESSION_STATUS_PENDING,
                Cv::COMPRESSION_STATUS_FAILED,
                Cv::COMPRESSION_STATUS_FAILED,
                '%introuvable%',
                Cv::COMPRESSION_STATUS_PENDING,
                Cv::COMPRESSION_STATUS_PROCESSING,
                Cv::COMPRESSION_STATUS_COMPLETED,
            ];
        } else {
            $select .= ',
                SUM(COALESCE(file_size, 0)) as total_current_size,
                0 as estimated_saved_space,
                0 as completed_files,
                0 as processing_files,
                0 as pending_files,
                0 as failed_files,
                0 as missing_files,
                0 as unoptimized_files
            ';
        }

        $row = (clone $query)->selectRaw($select, $bindings)->first();
        $total = (int) ($row->total_files ?? 0);
        $completed = (int) ($row->completed_files ?? 0);
        $failed = (int) ($row->failed_files ?? 0);
        $progress = $total > 0 ? (int) min(100, round((($completed + $failed) / $total) * 100)) : 0;

        return [
            'status' => ((int) ($row->processing_files ?? 0)) > 0 ? Cv::COMPRESSION_STATUS_PROCESSING : Cv::COMPRESSION_STATUS_PENDING,
            'total_files' => $total,
            'total_original_size' => (int) ($row->total_original_size ?? 0),
            'total_current_size' => (int) ($row->total_current_size ?? 0),
            'estimated_saved_space' => (int) ($row->estimated_saved_space ?? 0),
            'completed_files' => $completed,
            'processing_files' => (int) ($row->processing_files ?? 0),
            'pending_files' => (int) ($row->pending_files ?? 0),
            'failed_files' => $failed,
            'missing_files' => (int) ($row->missing_files ?? 0),
            'unoptimized_files' => (int) ($row->unoptimized_files ?? 0),
            'queued_jobs' => 0,
            'progress_percentage' => $progress,
            'estimated_seconds_remaining' => null,
            'estimated_time_remaining' => 'Non calcule',
            'status_message' => 'Statistiques du filtre courant.',
        ];
    }

    private function lightweightStorageStats(int|string $cvListTotal): array
    {
        $total = is_numeric($cvListTotal) ? (int) $cvListTotal : 0;

        return [
            'total_files' => $total,
            'total_original_size' => 0,
            'total_current_size' => 0,
            'estimated_saved_space' => 0,
            'compression_completed_files' => 0,
            'compression_processing_files' => 0,
            'compression_pending_files' => 0,
            'compression_failed_files' => 0,
            'compression_missing_files' => 0,
            'compression_progress' => [
                'completed_files' => 0,
                'processing_files' => 0,
                'pending_files' => 0,
                'failed_files' => 0,
                'missing_files' => 0,
                'queued_jobs' => 0,
                'unoptimized_files' => 0,
                'progress_percentage' => 0,
                'estimated_time_remaining' => 'Non charge',
                'status_message' => 'Statistiques chargees a la demande.',
            ],
            'keep_originals' => (bool) config('cv_storage.keep_originals', true),
            'verified_originals_removed_files' => 0,
        ];
    }

    private function extractTopDirectoryNameFromUpload(Request $request): ?string
    {
        $relativePaths = $request->input('relative_paths', []);

        if (!is_array($relativePaths) || empty($relativePaths)) {
            return null;
        }

        foreach ($relativePaths as $path) {
            $path = trim((string) $path);

            if ($path === '') {
                continue;
            }

            $path = str_replace('\\', '/', $path);
            $parts = array_values(array_filter(explode('/', $path)));

            if (count($parts) >= 2) {
                return $parts[0];
            }
        }

        return null;
    }

    private function guessMimeTypeFromExtension(string $extension): string
    {
        $extension = strtolower((string) $extension);

        return match ($extension) {
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt' => 'text/plain',
            default => 'application/octet-stream',
        };
    }
}
