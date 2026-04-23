<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CvFolder extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'created_by',
    ];

    protected static function booted()
    {
        static::saving(function (CvFolder $folder) {
            if (!$folder->slug) {
                $folder->slug = Str::slug($folder->name);
            }
        });
    }

    public function cvs()
    {
        return $this->hasMany(Cv::class, 'cv_folder_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}