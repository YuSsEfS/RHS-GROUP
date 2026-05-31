<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecruitmentRequest extends Model
{
    protected $fillable = [
        'job_offer_id',
        'cv_folder_id',
        'client_user_id',
        'assigned_employee_id',
        'assignment_status',
        'assignment_seen_at',

        'reference',
        'client_name',
        'logo_path',
        'request_date',
        'position_title',

        'work_location',
        'work_locations',

        'recruitment_reason',
        'age',
        'candidate_count',
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
        'pipeline_stage',
        'matching_job_status',
        'matching_status',
        'matching_started_at',
        'matching_completed_at',
        'matching_finished_at',
        'matching_error_message',
        'matching_error',
        'matching_viewed_at',
        'admin_notes',
        'employee_notes',
        'admin_seen_at',
    ];

    protected $casts = [
        'request_date' => 'date',
        'planned_start_date' => 'date',
        'admin_seen_at' => 'datetime',
        'assignment_seen_at' => 'datetime',
        'matching_started_at' => 'datetime',
        'matching_completed_at' => 'datetime',
        'matching_finished_at' => 'datetime',
        'matching_viewed_at' => 'datetime',

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

    public const ASSIGNMENT_STATUS_ASSIGNED = 'assigned';
    public const ASSIGNMENT_STATUS_IN_PROGRESS = 'in_progress';
    public const ASSIGNMENT_STATUS_COMPLETED = 'completed';

    public const PIPELINE_STAGE_NEW = 'nouvelle_demande';
    public const PIPELINE_STAGE_ANALYSIS = 'analyse';
    public const PIPELINE_STAGE_MATCHING = 'matching';
    public const PIPELINE_STAGE_SHORTLIST = 'shortlist';
    public const PIPELINE_STAGE_CLIENT_FEEDBACK = 'client_feedback';
    public const PIPELINE_STAGE_FINALIZATION = 'finalisation';
    public const PIPELINE_STAGE_CLOSED = 'cloture';

    public const JOB_STATUS_PENDING = 'en_attente';
    public const JOB_STATUS_RUNNING = 'en_cours';
    public const JOB_STATUS_DONE = 'termine';
    public const JOB_STATUS_FAILED = 'echoue';
    public const JOB_STATUS_CANCELLED = 'annule';

    public const MATCHING_STATUS_PENDING = 'pending';
    public const MATCHING_STATUS_PROCESSING = 'processing';
    public const MATCHING_STATUS_COMPLETED = 'completed';
    public const MATCHING_STATUS_FAILED = 'failed';
    public const MATCHING_STATUS_CANCELLED = 'cancelled';

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

    public static function availableAssignmentStatuses(): array
    {
        return [
            self::ASSIGNMENT_STATUS_ASSIGNED => 'Assignee',
            self::ASSIGNMENT_STATUS_IN_PROGRESS => 'En cours',
            self::ASSIGNMENT_STATUS_COMPLETED => 'Terminee',
        ];
    }

    public static function availablePipelineStages(): array
    {
        return [
            self::PIPELINE_STAGE_NEW => 'Nouvelle demande',
            self::PIPELINE_STAGE_ANALYSIS => 'Analyse',
            self::PIPELINE_STAGE_MATCHING => 'Matching',
            self::PIPELINE_STAGE_SHORTLIST => 'Shortlist',
            self::PIPELINE_STAGE_CLIENT_FEEDBACK => 'Retour client',
            self::PIPELINE_STAGE_FINALIZATION => 'Finalisation',
            self::PIPELINE_STAGE_CLOSED => 'Cloture',
        ];
    }

    public static function availableJobStatuses(): array
    {
        return [
            self::JOB_STATUS_PENDING => 'En attente',
            self::JOB_STATUS_RUNNING => 'En cours',
            self::JOB_STATUS_DONE => 'Termine',
            self::JOB_STATUS_FAILED => 'Echoue',
            self::JOB_STATUS_CANCELLED => 'Annule',
        ];
    }

    public static function availableMatchingStatuses(): array
    {
        return [
            self::MATCHING_STATUS_PENDING => 'En attente',
            self::MATCHING_STATUS_PROCESSING => 'En cours',
            self::MATCHING_STATUS_COMPLETED => 'Termine',
            self::MATCHING_STATUS_FAILED => 'Echoue',
            self::MATCHING_STATUS_CANCELLED => 'Annule',
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

    public function assignedEmployee()
    {
        return $this->belongsTo(User::class, 'assigned_employee_id');
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (empty($this->logo_path)) {
            return null;
        }

        return route('public.file', ltrim($this->logo_path, '/'));
    }

    public function clientAlerts()
    {
        return $this->hasMany(ClientRequestAlert::class);
    }

    public function isClientRequest(): bool
    {
        return !is_null($this->client_user_id);
    }

    public function isAssignedTo(?User $user): bool
    {
        return $user !== null && (int) $this->assigned_employee_id === (int) $user->id;
    }

    public function markMatchingQueued(): void
    {
        $this->forceFill([
            'matching_job_status' => self::JOB_STATUS_PENDING,
            'matching_status' => self::MATCHING_STATUS_PENDING,
            'matching_started_at' => null,
            'matching_completed_at' => null,
            'matching_finished_at' => null,
            'matching_error_message' => null,
            'matching_error' => null,
            'matching_viewed_at' => null,
        ])->save();
    }

    public function markMatchingRunning(): void
    {
        $startedAt = $this->matching_started_at ?: now();

        $this->forceFill([
            'matching_job_status' => self::JOB_STATUS_RUNNING,
            'matching_status' => self::MATCHING_STATUS_PROCESSING,
            'matching_started_at' => $startedAt,
            'matching_completed_at' => null,
            'matching_finished_at' => null,
            'matching_error_message' => null,
            'matching_error' => null,
        ])->save();
    }

    public function markMatchingCompleted(): void
    {
        $finishedAt = now();

        $this->forceFill([
            'matching_job_status' => self::JOB_STATUS_DONE,
            'matching_status' => self::MATCHING_STATUS_COMPLETED,
            'matching_completed_at' => $finishedAt,
            'matching_finished_at' => $finishedAt,
            'matching_error_message' => null,
            'matching_error' => null,
        ])->save();
    }

    public function markMatchingFailed(\Throwable|string $error): void
    {
        $message = mb_substr((string) ($error instanceof \Throwable ? $error->getMessage() : $error), 0, 2000);
        $finishedAt = now();

        $this->forceFill([
            'matching_job_status' => self::JOB_STATUS_FAILED,
            'matching_status' => self::MATCHING_STATUS_FAILED,
            'matching_completed_at' => $finishedAt,
            'matching_finished_at' => $finishedAt,
            'matching_error_message' => $message,
            'matching_error' => $message,
        ])->save();
    }

    public function markMatchingCancelled(): void
    {
        $finishedAt = now();

        $this->forceFill([
            'matching_job_status' => self::JOB_STATUS_CANCELLED,
            'matching_status' => self::MATCHING_STATUS_CANCELLED,
            'matching_completed_at' => $finishedAt,
            'matching_finished_at' => $finishedAt,
            'matching_error_message' => 'Matching annule par l utilisateur.',
            'matching_error' => 'Matching annule par l utilisateur.',
        ])->save();
    }

    public function resolveMatchingStatus(): ?string
    {
        $status = $this->matching_status;

        if ($status === self::MATCHING_STATUS_CANCELLED || $this->matching_job_status === self::JOB_STATUS_CANCELLED) {
            return self::MATCHING_STATUS_CANCELLED;
        }

        if ($this->resolveMatchingError()) {
            return self::MATCHING_STATUS_FAILED;
        }

        if ($status === self::MATCHING_STATUS_FAILED || $this->matching_job_status === self::JOB_STATUS_FAILED) {
            return self::MATCHING_STATUS_FAILED;
        }

        if ($status === self::MATCHING_STATUS_COMPLETED || $this->matching_job_status === self::JOB_STATUS_DONE) {
            return self::MATCHING_STATUS_COMPLETED;
        }

        if ($status === self::MATCHING_STATUS_PROCESSING || $this->matching_job_status === self::JOB_STATUS_RUNNING) {
            return self::MATCHING_STATUS_PROCESSING;
        }

        if (
            in_array($status, [null, '', self::MATCHING_STATUS_PENDING], true)
            && $this->hasResolvedMatches()
        ) {
            return self::MATCHING_STATUS_COMPLETED;
        }

        if (!empty($status)) {
            return $status;
        }

        return match ($this->matching_job_status) {
            self::JOB_STATUS_PENDING => self::MATCHING_STATUS_PENDING,
            self::JOB_STATUS_RUNNING => self::MATCHING_STATUS_PROCESSING,
            self::JOB_STATUS_DONE => self::MATCHING_STATUS_COMPLETED,
            self::JOB_STATUS_FAILED => self::MATCHING_STATUS_FAILED,
            self::JOB_STATUS_CANCELLED => self::MATCHING_STATUS_CANCELLED,
            default => null,
        };
    }

    public function resolveMatchingFinishedAt()
    {
        if ($this->matching_finished_at) {
            return $this->matching_finished_at;
        }

        if ($this->matching_completed_at) {
            return $this->matching_completed_at;
        }

        if ($this->resolveMatchingStatus() === self::MATCHING_STATUS_COMPLETED) {
            return $this->updated_at;
        }

        return null;
    }

    public function resolveMatchingError(): ?string
    {
        return $this->matching_error ?: $this->matching_error_message;
    }

    public function hasUnreadMatchingResults(): bool
    {
        if ($this->resolveMatchingStatus() !== self::MATCHING_STATUS_COMPLETED) {
            return false;
        }

        $finishedAt = $this->resolveMatchingFinishedAt();

        if (!$finishedAt) {
            return false;
        }

        return $this->matching_viewed_at === null || $finishedAt->gt($this->matching_viewed_at);
    }

    public function markMatchingViewed(): void
    {
        static::withoutTimestamps(function () {
            $this->forceFill([
                'matching_viewed_at' => now(),
            ])->saveQuietly();
        });
    }

    public function hasResolvedMatches(): bool
    {
        if (array_key_exists('matches_count', $this->attributes)) {
            return (int) $this->attributes['matches_count'] > 0;
        }

        if ($this->relationLoaded('matches')) {
            return $this->matches->isNotEmpty();
        }

        return $this->matches()->exists();
    }

    public function syncResolvedMatchingStateIfNeeded(): bool
    {
        $resolvedStatus = $this->resolveMatchingStatus();
        $resolvedFinishedAt = $this->resolveMatchingFinishedAt();
        $resolvedError = $this->resolveMatchingError();
        $updates = [];

        if ($resolvedStatus && $resolvedStatus !== $this->matching_status) {
            $updates['matching_status'] = $resolvedStatus;
        }

        if (
            $resolvedStatus === self::MATCHING_STATUS_COMPLETED
            && $resolvedFinishedAt
            && !$this->matching_finished_at
        ) {
            $updates['matching_finished_at'] = $resolvedFinishedAt;
        }

        if (
            $resolvedStatus === self::MATCHING_STATUS_COMPLETED
            && $resolvedFinishedAt
            && !$this->matching_completed_at
        ) {
            $updates['matching_completed_at'] = $resolvedFinishedAt;
        }

        if (
            $resolvedStatus === self::MATCHING_STATUS_FAILED
            && $resolvedError
            && empty($this->matching_error)
        ) {
            $updates['matching_error'] = $resolvedError;
        }

        if (
            $resolvedStatus === self::MATCHING_STATUS_FAILED
            && $resolvedError
            && empty($this->matching_error_message)
        ) {
            $updates['matching_error_message'] = $resolvedError;
        }

        if (empty($updates)) {
            return false;
        }

        static::withoutTimestamps(function () use ($updates) {
            $this->forceFill($updates)->saveQuietly();
        });

        $this->forceFill($updates);

        return true;
    }
}
