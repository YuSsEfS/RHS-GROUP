<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Formation extends Model
{
    protected $fillable = [
        'title',
        'domain',
        'public',
        'format',
        'duration',
        'audience',
        'format_label',
        'description',
        'program',
        'featured',
    ];

    protected $casts = [
        'featured' => 'boolean',
    ];
}
