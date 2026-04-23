<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalCv extends Model
{
    protected $fillable = [
        'batch_id',
        'cv_id',
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
}