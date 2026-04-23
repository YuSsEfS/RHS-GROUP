<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecruitmentRequest extends Model
{
    protected $fillable = [
        'job_offer_id',
        'reference',
        'client_name',
        'request_date',
        'position_title',
        'work_location',
        'recruitment_reason',
        'age',
        'gender',
        'education',
        'experience_years',
        'availability',
        'other_language',
        'budget_type',
        'monthly_salary',
        'contract_type',
        'planned_start_date',
        'missions',
        'personal_qualities',
        'specific_knowledge',
        'other_benefits',
        'lang_ar',
        'lang_fr',
        'lang_en',
        'lang_es',
        'ai_normalized_requirements',
    ];

    protected $casts = [
        'lang_ar' => 'boolean',
        'lang_fr' => 'boolean',
        'lang_en' => 'boolean',
        'lang_es' => 'boolean',
        'ai_normalized_requirements' => 'array',
    ];

    public function matches()
    {
        return $this->hasMany(CvMatch::class);
    }

    public function jobOffer()
    {
        return $this->belongsTo(JobOffer::class);
    }
}