<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobilePushToken extends Model
{
    protected $fillable = [
        'user_id',
        'expo_push_token',
        'platform',
        'device_name',
        'last_counts',
        'last_registered_at',
        'last_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'last_counts' => 'array',
            'last_registered_at' => 'datetime',
            'last_notified_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
