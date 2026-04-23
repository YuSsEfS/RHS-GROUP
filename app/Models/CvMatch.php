<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CvMatch extends Model
{
    protected $fillable = [
        'recruitment_request_id',
        'cv_id',
        'score',
        'score_breakdown',
        'summary',
        'selected',
    ];

    protected $casts = [
        'score_breakdown' => 'array',
        'selected' => 'boolean',
    ];

    public function recruitmentRequest()
    {
        return $this->belongsTo(RecruitmentRequest::class);
    }

    public function cv()
    {
        return $this->belongsTo(Cv::class);
    }
}