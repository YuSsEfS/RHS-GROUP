<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class JobOffer extends Model
{
    protected $fillable = [
        'title','slug','company','location','contract_type','sector','hero_image','excerpt','description','missions','requirements',
        'is_active','published_at'
    ];

    protected static function booted()
    {
        static::saving(function (JobOffer $offer) {
            if (!$offer->slug) $offer->slug = Str::slug($offer->title);
        });
    }
   protected $casts = [
    'is_active'    => 'boolean',
    'published_at' => 'datetime',
];
public function recruitmentRequests()
{
    return $this->hasMany(\App\Models\RecruitmentRequest::class);
}

}
