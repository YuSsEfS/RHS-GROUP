<?php

namespace App\Services;

use App\Models\Cv;
use App\Models\ExternalCv;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class CvStorageOptimizationService
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public function availableStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'En attente',
            self::STATUS_PROCESSING => 'En cours',
            self::STATUS_COMPLETED => 'Optimise',
            self::STATUS_FAILED => 'Echec',
        ];
    }

    public function storageStats(): array
    {
        if (
            !Schema::hasTable('cvs')
            || !Schema::hasColumn('cvs', 'original_file_size')
            || !Schema::hasColumn('cvs', 'compression_status')
        ) {
            return [
                'total_files' => Cv::count(),
                'total_original_size' => (int) Cv::sum('file_size'),
                'total_current_size' => (int) Cv::sum('file_size'),
                'estimated_saved_space' => 0,
                'compression_completed_files' => 0,
                'compression_processing_files' => 0,
                'compression_pending_files' => 0,
                'compression_failed_files' => 0,
                'compression_missing_files' => 0,
                'compression_progress' => $this->compressionProgress(),
                'keep_originals' => (bool) config('cv_storage.keep_originals', true),
                'verified_originals_removed_files' => $this->verifiedOriginalsRemovedCount(),
            ];
        }

        $keepOriginals = (bool) config('cv_storage.keep_originals', true);
        $totals = Cv::query()->selectRaw('
            COUNT(*) as total_files,
            SUM(COALESCE(original_file_size, file_size, 0)) as total_original_size,
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
            ) as estimated_saved_space
        ', [self::STATUS_COMPLETED])->first();
        $statusCounts = Cv::query()
            ->selectRaw("
                SUM(CASE WHEN compression_status = ? AND compression_verified_at IS NOT NULL THEN 1 ELSE 0 END) as completed_files,
                SUM(CASE WHEN compression_status = ? THEN 1 ELSE 0 END) as processing_files,
                SUM(CASE WHEN compression_status = ? THEN 1 ELSE 0 END) as pending_files,
                SUM(CASE WHEN compression_status = ? THEN 1 ELSE 0 END) as failed_files,
                SUM(CASE WHEN compression_status = ? AND compression_error LIKE ? THEN 1 ELSE 0 END) as missing_files
            ", [
                self::STATUS_COMPLETED,
                self::STATUS_PROCESSING,
                self::STATUS_PENDING,
                self::STATUS_FAILED,
                self::STATUS_FAILED,
                '%introuvable%',
            ])
            ->first();

        $totalFiles = (int) ($totals->total_files ?? 0);
        $completedFiles = (int) ($statusCounts->completed_files ?? 0);
        $failedFiles = (int) ($statusCounts->failed_files ?? 0);
        $progressPercentage = $totalFiles > 0
            ? (int) min(100, round((($completedFiles + $failedFiles) / $totalFiles) * 100))
            : 0;

        return [
            'total_files' => $totalFiles,
            'total_original_size' => (int) ($totals->total_original_size ?? 0),
            'total_current_size' => (int) ($totals->total_current_size ?? 0),
            'estimated_saved_space' => (int) ($totals->estimated_saved_space ?? 0),
            'compression_completed_files' => $completedFiles,
            'compression_processing_files' => (int) ($statusCounts->processing_files ?? 0),
            'compression_pending_files' => (int) ($statusCounts->pending_files ?? 0),
            'compression_failed_files' => (int) ($statusCounts->failed_files ?? 0),
            'compression_missing_files' => (int) ($statusCounts->missing_files ?? 0),
            'compression_progress' => [
                'completed_files' => $completedFiles,
                'processing_files' => (int) ($statusCounts->processing_files ?? 0),
                'pending_files' => (int) ($statusCounts->pending_files ?? 0),
                'failed_files' => $failedFiles,
                'missing_files' => (int) ($statusCounts->missing_files ?? 0),
                'queued_jobs' => 0,
                'unoptimized_files' => 0,
                'progress_percentage' => $progressPercentage,
                'estimated_time_remaining' => 'Calcul en cours',
                'status_message' => 'Compression en arriere-plan.',
            ],
            'keep_originals' => $keepOriginals,
            'verified_originals_removed_files' => 0,
        ];
    }

    public function compressionProgress(): array
    {
        if (!$this->hasCompressionColumns()) {
            return [
                'status' => self::STATUS_PENDING,
                'total_files' => 0,
                'completed_files' => 0,
                'processing_files' => 0,
                'pending_files' => 0,
                'failed_files' => 0,
                'missing_files' => 0,
                'unoptimized_files' => 0,
                'processed_items' => 0,
                'remaining_items' => 0,
                'queued_jobs' => 0,
                'progress_percentage' => 0,
                'estimated_seconds_remaining' => null,
                'estimated_time_remaining' => 'Calcul en cours',
                'status_message' => 'Colonnes de compression indisponibles.',
            ];
        }

        $baseQuery = Cv::query()
            ->when(
                Schema::hasColumn('cvs', 'archived_at'),
                fn ($query) => $query->whereNull('archived_at')
            );

        $counts = (clone $baseQuery)->selectRaw("
            COUNT(*) as total_files,
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
        ", [
            self::STATUS_COMPLETED,
            self::STATUS_PROCESSING,
            self::STATUS_PENDING,
            self::STATUS_FAILED,
            self::STATUS_FAILED,
            '%introuvable%',
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
            self::STATUS_COMPLETED,
        ])->first();

        $totalFiles = (int) ($counts->total_files ?? 0);
        $completedFiles = (int) ($counts->completed_files ?? 0);
        $processingFiles = (int) ($counts->processing_files ?? 0);
        $pendingFiles = (int) ($counts->pending_files ?? 0);
        $failedFiles = (int) ($counts->failed_files ?? 0);
        $missingFiles = (int) ($counts->missing_files ?? 0);
        $unoptimizedFiles = (int) ($counts->unoptimized_files ?? 0);

        $status = match (true) {
            $processingFiles > 0 => self::STATUS_PROCESSING,
            $pendingFiles > 0 => self::STATUS_PENDING,
            $totalFiles > 0 && ($completedFiles + $failedFiles) >= $totalFiles && $failedFiles > 0 => self::STATUS_FAILED,
            $totalFiles > 0 && $completedFiles >= $totalFiles => self::STATUS_COMPLETED,
            default => self::STATUS_PENDING,
        };

        $startedAt = (clone $baseQuery)
            ->whereNotNull('compression_status')
            ->whereIn('compression_status', [
                self::STATUS_PENDING,
                self::STATUS_PROCESSING,
                self::STATUS_COMPLETED,
                self::STATUS_FAILED,
            ])
            ->min('updated_at');

        $recent = $this->recentCompressionThroughput($baseQuery);
        $eta = app(ProcessingEtaService::class)->payload(
            processed: $completedFiles + $failedFiles,
            total: $totalFiles,
            startedAt: $startedAt ? \Illuminate\Support\Carbon::parse($startedAt) : null,
            status: $status,
            recentProcessed: $recent['processed'],
            recentWindowSeconds: $recent['window_seconds'],
            preferRecent: true
        );
        $queuedJobs = $this->queuedCompressionJobsCount();
        $statusMessage = 'Compression en arriere-plan.';

        if ($processingFiles === 0 && $pendingFiles > 0 && $queuedJobs === 0) {
            $eta['estimated_seconds_remaining'] = null;
            $eta['estimated_time_remaining'] = 'En attente';
            $statusMessage = 'Des CV sont en attente mais aucun job compression actif n est detecte. Relancez la compression ou demarrez le worker compression.';
        } elseif ($processingFiles === 0 && ($pendingFiles > 0 || $queuedJobs > 0) && ($completedFiles + $failedFiles) < 5) {
            $eta['estimated_seconds_remaining'] = null;
            $eta['estimated_time_remaining'] = 'En attente du worker';
            $statusMessage = 'Des CV sont en attente. Lancez le worker de queue pour demarrer la compression.';
        } elseif ($processingFiles === 0 && $pendingFiles > 0) {
            $statusMessage = 'Des CV restent en attente. Relancez le worker si le compteur ne bouge plus.';
        } elseif ($processingFiles > 0) {
            $statusMessage = 'Compression en cours en arriere-plan.';
        } elseif ($missingFiles > 0 && $failedFiles === $missingFiles) {
            $eta['estimated_seconds_remaining'] = 0;
            $eta['estimated_time_remaining'] = 'Termine';
            $statusMessage = 'Compression terminee pour les fichiers disponibles. Les echecs restants correspondent a des fichiers originaux manquants.';
        } elseif ($failedFiles > 0) {
            $statusMessage = 'Compression terminee avec des echecs. Utilisez Recompresser les echecs pour relancer uniquement les fichiers recuperables.';
        } elseif ($completedFiles > 0 || $failedFiles > 0) {
            $statusMessage = 'Traitement de compression termine pour les CV planifies.';
        }

        return array_merge($eta, [
            'status' => $status,
            'total_files' => $totalFiles,
            'completed_files' => $completedFiles,
            'processing_files' => $processingFiles,
            'pending_files' => $pendingFiles,
            'failed_files' => $failedFiles,
            'missing_files' => $missingFiles,
            'unoptimized_files' => $unoptimizedFiles,
            'queued_jobs' => $queuedJobs,
            'status_message' => $statusMessage,
            'verified_originals_removed_files' => $this->verifiedOriginalsRemovedCount(),
            'keep_originals' => (bool) config('cv_storage.keep_originals', true),
        ]);
    }

    public function markQueued(Cv $cv): void
    {
        if (!$this->hasCompressionColumns()) {
            return;
        }

        $cv->forceFill([
            'compression_status' => self::STATUS_PENDING,
            'compression_error' => null,
        ])->saveQuietly();
    }

    public function markRunning(Cv $cv): void
    {
        if (!$this->hasCompressionColumns()) {
            return;
        }

        $cv->forceFill([
            'compression_status' => self::STATUS_PROCESSING,
            'compression_error' => null,
        ])->saveQuietly();
    }

    public function compress(Cv $cv): array
    {
        if (!config('cv_storage.enable_compression', true)) {
            throw new \RuntimeException('La compression de stockage est desactivee.');
        }

        if (!$this->hasCompressionColumns()) {
            throw new \RuntimeException('Les colonnes de compression ne sont pas encore migrees.');
        }

        if (
            $cv->compression_verified_at
            && !empty($cv->compressed_path)
            && Storage::disk($this->compressionDisk())->exists($cv->compressed_path)
        ) {
            $cv->forceFill([
                'compression_status' => self::STATUS_COMPLETED,
                'compression_error' => null,
            ])->saveQuietly();

            return [
                'status' => 'already_optimized',
                'original_size' => $cv->original_file_size,
                'compressed_size' => $cv->compressed_file_size,
            ];
        }

        $extension = strtolower(pathinfo((string) ($cv->original_filename ?: $cv->encrypted_path), PATHINFO_EXTENSION));
        $allowedExtensions = array_map('strtolower', config('cv_storage.allowed_extensions', []));

        if (!in_array($extension, $allowedExtensions, true)) {
            throw new \RuntimeException('Extension non prise en charge pour la compression.');
        }

        $originalPath = $this->resolveOriginalPath($cv);

        if (!$originalPath || !Storage::disk('local')->exists($originalPath)) {
            $cv->forceFill([
                'compression_status' => self::STATUS_FAILED,
                'compression_error' => 'Fichier original introuvable',
            ])->saveQuietly();

            return [
                'status' => 'missing_original_file',
            ];
        }

        $binary = Storage::disk('local')->get($originalPath);
        $originalSize = strlen($binary);

        if ($originalSize < (int) config('cv_storage.min_size_to_compress', 0)) {
            if (!empty($cv->compressed_path) && Storage::disk($this->compressionDisk())->exists($cv->compressed_path)) {
                Storage::disk($this->compressionDisk())->delete($cv->compressed_path);
            }

            $cv->forceFill([
                'original_file_size' => $originalSize,
                'compressed_file_size' => null,
                'compressed_path' => null,
                'compression_status' => self::STATUS_COMPLETED,
                'compression_error' => null,
                'compression_verified_at' => now(),
            ])->saveQuietly();

            return [
                'status' => 'skipped_small_file',
                'original_size' => $originalSize,
                'compressed_size' => null,
            ];
        }

        $compressedBinary = gzencode($binary, 9);

        if ($compressedBinary === false) {
            throw new \RuntimeException('La compression GZip du fichier a echoue.');
        }

        if (strlen($compressedBinary) >= $originalSize) {
            if (!empty($cv->compressed_path) && Storage::disk($this->compressionDisk())->exists($cv->compressed_path)) {
                Storage::disk($this->compressionDisk())->delete($cv->compressed_path);
            }

            $cv->forceFill([
                'original_file_size' => $originalSize,
                'compressed_file_size' => null,
                'compressed_path' => null,
                'compression_status' => self::STATUS_COMPLETED,
                'compression_error' => null,
                'compression_verified_at' => now(),
            ])->saveQuietly();

            return [
                'status' => 'no_gain',
                'original_size' => $originalSize,
                'compressed_size' => null,
            ];
        }

        $compressedPath = 'private/cv-compressed/' . $cv->id . '/' . uniqid('cv_', true) . '.gz';
        Storage::disk($this->compressionDisk())->put($compressedPath, $compressedBinary);

        $verifiedBinary = gzdecode(Storage::disk($this->compressionDisk())->get($compressedPath));

        if ($verifiedBinary === false || hash('sha256', $verifiedBinary) !== hash('sha256', $binary)) {
            Storage::disk($this->compressionDisk())->delete($compressedPath);

            throw new \RuntimeException('Verification de la compression echouee.');
        }

        if (!empty($cv->compressed_path) && $cv->compressed_path !== $compressedPath) {
            Storage::disk($this->compressionDisk())->delete($cv->compressed_path);
        }

        $cv->forceFill([
            'original_file_size' => $originalSize,
            'compressed_file_size' => strlen($compressedBinary),
            'compressed_path' => $compressedPath,
            'compression_status' => self::STATUS_COMPLETED,
            'compression_error' => null,
            'compression_verified_at' => now(),
        ])->saveQuietly();

        if (!config('cv_storage.keep_originals', true)) {
            $this->deleteVerifiedOriginalFile($cv->fresh() ?? $cv);
        }

        return [
            'status' => 'completed',
            'original_size' => $originalSize,
            'compressed_size' => strlen($compressedBinary),
            'compressed_path' => $compressedPath,
        ];
    }

    public function pruneVerifiedOriginals(int $limit = 500): int
    {
        if (config('cv_storage.keep_originals', true) || !$this->hasCompressionColumns()) {
            return 0;
        }

        $deleted = 0;

        Cv::query()
            ->where('compression_status', self::STATUS_COMPLETED)
            ->whereNotNull('compression_verified_at')
            ->whereNotNull('compressed_path')
            ->whereNotNull('encrypted_path')
            ->orderBy('id')
            ->limit(max(1, $limit))
            ->get()
            ->each(function (Cv $cv) use (&$deleted) {
                if ($this->deleteVerifiedOriginalFile($cv)) {
                    $deleted++;
                }
            });

        return $deleted;
    }

    public function markFailed(Cv $cv, \Throwable|string $error): void
    {
        if (!$this->hasCompressionColumns()) {
            return;
        }

        $message = mb_substr((string) ($error instanceof \Throwable ? $error->getMessage() : $error), 0, 2000);

        $cv->forceFill([
            'compression_status' => self::STATUS_FAILED,
            'compression_error' => $message,
        ])->saveQuietly();
    }

    public function readBinary(Cv $cv): ?string
    {
        $originalPath = $this->resolveOriginalPath($cv);

        if ($originalPath && Storage::disk('local')->exists($originalPath)) {
            return Storage::disk('local')->get($originalPath);
        }

        if (
            !empty($cv->compressed_path)
            && !empty($cv->compression_verified_at)
            && Storage::disk($this->compressionDisk())->exists($cv->compressed_path)
        ) {
            $decoded = gzdecode(Storage::disk($this->compressionDisk())->get($cv->compressed_path));

            return $decoded === false ? null : $decoded;
        }

        return null;
    }

    public function deleteStoredFiles(Cv $cv): void
    {
        if (
            !empty($cv->encrypted_path)
            && Storage::disk('local')->exists($cv->encrypted_path)
            && !$this->isOriginalFileShared($cv)
        ) {
            Storage::disk('local')->delete($cv->encrypted_path);
        }

        if (!empty($cv->compressed_path) && Storage::disk($this->compressionDisk())->exists($cv->compressed_path)) {
            Storage::disk($this->compressionDisk())->delete($cv->compressed_path);
        }
    }

    public function preserveExternalFileForCv(Cv $cv, string $sourcePath, string $originalFilename): ?string
    {
        if ($sourcePath === '' || !Storage::disk('local')->exists($sourcePath)) {
            return null;
        }

        $extension = strtolower(pathinfo($originalFilename ?: $sourcePath, PATHINFO_EXTENSION));
        $targetPath = 'private/cvs/' . uniqid('cv_preserved_', true) . ($extension ? '.' . $extension : '');

        Storage::disk('local')->copy($sourcePath, $targetPath);

        $cv->forceFill([
            'encrypted_path' => $targetPath,
        ])->saveQuietly();

        return $targetPath;
    }

    private function resolveOriginalPath(Cv $cv): ?string
    {
        return !empty($cv->encrypted_path) ? (string) $cv->encrypted_path : null;
    }

    private function hasCompressionColumns(): bool
    {
        return Schema::hasTable('cvs')
            && Schema::hasColumn('cvs', 'compression_status')
            && Schema::hasColumn('cvs', 'compressed_path')
            && Schema::hasColumn('cvs', 'compression_verified_at');
    }

    private function compressionDisk(): string
    {
        return (string) config('cv_storage.compression_disk', 'local');
    }

    private function isOriginalFileShared(Cv $cv): bool
    {
        if (empty($cv->encrypted_path)) {
            return false;
        }

        return Cv::query()
            ->whereKeyNot($cv->id)
            ->where('encrypted_path', $cv->encrypted_path)
            ->exists()
            || ExternalCv::query()
                ->where('stored_path', $cv->encrypted_path)
                ->exists();
    }

    private function deleteVerifiedOriginalFile(Cv $cv): bool
    {
        if (
            empty($cv->encrypted_path)
            || empty($cv->compressed_path)
            || empty($cv->compression_verified_at)
            || $cv->compression_status !== self::STATUS_COMPLETED
            || !Storage::disk($this->compressionDisk())->exists($cv->compressed_path)
            || !Storage::disk('local')->exists($cv->encrypted_path)
        ) {
            return false;
        }

        if ($this->isOriginalFileShared($cv)) {
            return false;
        }

        Storage::disk('local')->delete($cv->encrypted_path);

        return true;
    }

    private function verifiedOriginalsRemovedCount(): int
    {
        if (!$this->hasCompressionColumns()) {
            return 0;
        }

        return Cv::query()
            ->where('compression_status', self::STATUS_COMPLETED)
            ->whereNotNull('compression_verified_at')
            ->whereNotNull('compressed_path')
            ->where(function ($query) {
                $query->whereNull('encrypted_path')
                    ->orWhere('encrypted_path', '');
            })
            ->count();
    }

    private function recentCompressionThroughput($baseQuery): array
    {
        $minutes = max(1, (int) config('cv_storage.eta_recent_window_minutes', 10));
        $since = now()->subMinutes($minutes);
        $recentQuery = (clone $baseQuery)
            ->whereIn('compression_status', [self::STATUS_COMPLETED, self::STATUS_FAILED])
            ->where('updated_at', '>=', $since);

        $processed = (int) (clone $recentQuery)->count();
        $firstRecent = (clone $recentQuery)->min('updated_at');
        $windowSeconds = $firstRecent
            ? max(60, \Illuminate\Support\Carbon::parse($firstRecent)->diffInSeconds(now(), true))
            : $minutes * 60;

        return [
            'processed' => $processed,
            'window_seconds' => $windowSeconds,
        ];
    }

    private function queuedCompressionJobsCount(): int
    {
        if (!Schema::hasTable('jobs')) {
            return 0;
        }

        return DB::table('jobs')
            ->where('queue', 'compression')
            ->where(function ($query) {
                $query->where('payload', 'like', '%CompressCvFileJob%')
                    ->orWhere('payload', 'like', '%QueueUncompressedCvCompressionJob%')
                    ->orWhere('payload', 'like', '%QueueFailedCvCompressionJob%');
            })
            ->count();
    }
}
