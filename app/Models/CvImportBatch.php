<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CvImportBatch extends Model
{
    public const STATUS_PENDING = 'en_attente';
    public const STATUS_PROCESSING = 'en_cours';
    public const STATUS_DONE = 'termine';
    public const STATUS_FAILED = 'echoue';

    protected $fillable = [
        'name',
        'cv_folder_id',
        'total_files',
        'queued_files',
        'processed_files',
        'failed_files',
        'duplicate_files',
        'status',
        'error_message',
        'created_by',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public static function availableStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'En attente',
            self::STATUS_PROCESSING => 'En cours',
            self::STATUS_DONE => 'Termine',
            self::STATUS_FAILED => 'Echoue',
        ];
    }

    public function folder()
    {
        return $this->belongsTo(CvFolder::class, 'cv_folder_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pendingFilesCount(): int
    {
        return max(
            0,
            (int) $this->total_files - ((int) $this->processed_files + (int) $this->failed_files + (int) $this->duplicate_files)
        );
    }

    public function progressPercentage(): int
    {
        $total = max(1, (int) $this->total_files);
        $completed = min($total, (int) $this->processed_files + (int) $this->failed_files + (int) $this->duplicate_files);

        return (int) round(($completed / $total) * 100);
    }

    public function refreshProgressState(): void
    {
        $completed = (int) $this->processed_files + (int) $this->failed_files + (int) $this->duplicate_files;

        if ((int) $this->total_files <= 0) {
            $status = self::STATUS_PENDING;
        } elseif ($completed < (int) $this->total_files) {
            $status = self::STATUS_PROCESSING;
        } elseif ((int) $this->failed_files > 0) {
            $status = self::STATUS_FAILED;
        } else {
            $status = self::STATUS_DONE;
        }

        $this->forceFill([
            'status' => $status,
            'started_at' => $this->started_at ?: now(),
            'finished_at' => $completed >= (int) $this->total_files && (int) $this->total_files > 0
                ? ($this->finished_at ?: now())
                : null,
        ])->saveQuietly();
    }
}
