<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalCvBatch extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public const PROCESSING_STATUS_PENDING = 'en_attente';
    public const PROCESSING_STATUS_RUNNING = 'en_cours';
    public const PROCESSING_STATUS_DONE = 'termine';
    public const PROCESSING_STATUS_FAILED = 'echoue';

    protected $fillable = [
        'name',
        'notes',
        'cv_folder_id',
        'total_files',
        'indexed_files',
        'failed_files',
        'duplicate_files',
        'status',
        'processing_status',
        'processing_started_at',
        'processing_completed_at',
        'processing_error_message',
        'created_by',
    ];

    protected $casts = [
        'processing_started_at' => 'datetime',
        'processing_completed_at' => 'datetime',
    ];

    public static function availableStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_PENDING => 'En attente',
            self::STATUS_PROCESSING => 'En cours',
            self::STATUS_COMPLETED => 'Termine',
            self::STATUS_FAILED => 'Echoue',
        ];
    }

    public function files()
    {
        return $this->hasMany(ExternalCv::class, 'batch_id');
    }

    public function cvs()
    {
        return $this->hasMany(ExternalCv::class, 'batch_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function folder()
    {
        return $this->belongsTo(CvFolder::class, 'cv_folder_id');
    }

    public function pendingFilesCount(): int
    {
        return max(
            0,
            (int) $this->total_files - ((int) $this->indexed_files + (int) $this->failed_files + (int) $this->duplicate_files)
        );
    }

    public function progressPercentage(): int
    {
        $total = max(1, (int) $this->total_files);
        $completed = min($total, (int) $this->indexed_files + (int) $this->failed_files + (int) $this->duplicate_files);

        return (int) round(($completed / $total) * 100);
    }
}
