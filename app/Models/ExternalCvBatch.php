<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalCvBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'notes',
        'cv_folder_id',
        'total_files',
        'indexed_files',
        'failed_files',
        'status',
        'created_by',
    ];

    public function files()
    {
        return $this->hasMany(ExternalCv::class, 'batch_id');
    }

    public function cvs()
    {
        return $this->hasMany(ExternalCv::class, 'batch_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function folder()
    {
        return $this->belongsTo(CvFolder::class, 'cv_folder_id');
    }
}