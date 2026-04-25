<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecruitmentRequest extends Model
{
    protected $fillable = [
        'job_offer_id',
        'cv_folder_id',
        'client_user_id',

        'reference',
        'client_name',
        'request_date',
        'position_title',

        'work_location',
        'work_locations',

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
        'request_status',
        'admin_notes',
        'admin_seen_at',
    ];

    protected $casts = [
        'request_date' => 'date',
        'planned_start_date' => 'date',
        'admin_seen_at' => 'datetime',

        'lang_ar' => 'boolean',
        'lang_fr' => 'boolean',
        'lang_en' => 'boolean',
        'lang_es' => 'boolean',

        'ai_normalized_requirements' => 'array',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_MATCHING_IN_PROGRESS = 'matching_in_progress';
    public const STATUS_SHORTLISTED = 'shortlisted';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    public static function availableStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'En attente',
            self::STATUS_UNDER_REVIEW => 'En cours de revue',
            self::STATUS_MATCHING_IN_PROGRESS => 'Matching en cours',
            self::STATUS_SHORTLISTED => 'Preselection finalisee',
            self::STATUS_COMPLETED => 'Traitee',
            self::STATUS_REJECTED => 'Rejetee',
            self::STATUS_CANCELLED => 'Annulee',
        ];
    }

    public function jobOffer()
    {
        return $this->belongsTo(JobOffer::class);
    }

    public function folder()
    {
        return $this->belongsTo(CvFolder::class, 'cv_folder_id');
    }

    public function matches()
    {
        return $this->hasMany(CvMatch::class);
    }

    public function clientUser()
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function clientAlerts()
    {
        return $this->hasMany(ClientRequestAlert::class);
    }

    public function isClientRequest(): bool
    {
        return !is_null($this->client_user_id);
    }
}
