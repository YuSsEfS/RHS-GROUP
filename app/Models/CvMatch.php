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
        'ai_analysis_status',
        'ai_analysis_started_at',
        'ai_analysis_completed_at',
        'ai_analysis_error_message',
    ];

    protected $casts = [
        'score_breakdown' => 'array',
        'selected' => 'boolean',
        'ai_analysis_started_at' => 'datetime',
        'ai_analysis_completed_at' => 'datetime',
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
