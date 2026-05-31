<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalCv extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_INDEXED = 'indexed';
    public const STATUS_DUPLICATE = 'duplicate';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'batch_id',
        'cv_id',
        'duplicate_of_cv_id',
        'duplicate_score',
        'duplicate_reason',
        'candidate_name',
        'email',
        'phone',
        'city',
        'current_title',
        'original_filename',
        'mime_type',
        'file_size',
        'stored_path',
        'file_hash',
        'extracted_text',
        'structured_profile',
        'status',
        'error_message',
        'indexed_at',
    ];

    protected $casts = [
        'structured_profile' => 'array',
        'duplicate_score' => 'float',
        'indexed_at' => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(ExternalCvBatch::class, 'batch_id');
    }

    public function cv()
    {
        return $this->belongsTo(Cv::class, 'cv_id');
    }

    public function duplicateOf()
    {
        return $this->belongsTo(Cv::class, 'duplicate_of_cv_id');
    }
}
