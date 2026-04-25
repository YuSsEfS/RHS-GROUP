<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    protected $fillable = [
        'job_offer_id',
        'full_name',
        'email',
        'phone',
        'city',
        'position',      // ✅ add this
        'type',          // ✅ add this (optional but needed for your email display)
        'cv_path',
        'letter_path',
        'message',
        'is_read',
        'admin_seen_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'admin_seen_at' => 'datetime',
    ];

    public function offer()
    {
        return $this->belongsTo(JobOffer::class, 'job_offer_id');
    }
}
