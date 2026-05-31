<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cv extends Model
{
    public const COMPRESSION_STATUS_PENDING = 'pending';
    public const COMPRESSION_STATUS_PROCESSING = 'processing';
    public const COMPRESSION_STATUS_COMPLETED = 'completed';
    public const COMPRESSION_STATUS_FAILED = 'failed';

    protected $fillable = [
        'candidate_name',
        'email',
        'phone',
        'original_filename',
        'mime_type',
        'file_size',
        'encrypted_path',
        'encrypted_extracted_text',
        'structured_profile',
        'file_hash',
        'uploaded_at',

        // CV bank fields
        'source_type',
        'source_id',
        'cv_folder_id',
        'city',
        'current_title',
        'is_active',
        'archived_at',
        'archived_by',
        'archive_reason',
        'notes',
        'duplicate_of_cv_id',
        'duplicate_score',
        'duplicate_reason',
        'original_file_size',
        'compressed_file_size',
        'compressed_path',
        'compression_status',
        'compression_error',
        'compression_verified_at',
    ];

    protected $casts = [
        'structured_profile' => 'array',
        'uploaded_at' => 'datetime',
        'is_active' => 'boolean',
        'archived_at' => 'datetime',
        'duplicate_score' => 'float',
        'compression_verified_at' => 'datetime',
    ];

    public static function availableCompressionStatuses(): array
    {
        return [
            self::COMPRESSION_STATUS_PENDING => 'En attente',
            self::COMPRESSION_STATUS_PROCESSING => 'En cours',
            self::COMPRESSION_STATUS_COMPLETED => 'Optimise',
            self::COMPRESSION_STATUS_FAILED => 'Echec',
        ];
    }

    public function folder()
    {
        return $this->belongsTo(CvFolder::class, 'cv_folder_id');
    }

    public function matches()
    {
        return $this->hasMany(CvMatch::class);
    }

    public function duplicateOf()
    {
        return $this->belongsTo(self::class, 'duplicate_of_cv_id');
    }

    public function archivedBy()
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function isArchived(): bool
    {
        return !is_null($this->archived_at);
    }

    public function getDisplaySourceAttribute(): string
    {
        return match ($this->source_type) {
            'application' => 'Application',
            'external_db' => 'External DB',
            'manual' => 'Manual Upload',
            default => 'Unknown',
        };
    }

    public function externalImports()
    {
        return $this->hasMany(ExternalCv::class, 'cv_id');
    }
}
