<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cv extends Model
{
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
        'notes',
    ];

    protected $casts = [
        'structured_profile' => 'array',
        'uploaded_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function folder()
    {
        return $this->belongsTo(CvFolder::class, 'cv_folder_id');
    }

    public function matches()
    {
        return $this->hasMany(CvMatch::class);
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
}