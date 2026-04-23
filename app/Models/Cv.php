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
    ];

    protected $casts = [
        'structured_profile' => 'array',
        'uploaded_at' => 'datetime',
    ];
}