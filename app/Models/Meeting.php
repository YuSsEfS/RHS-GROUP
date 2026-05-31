<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'title',
        'description',
        'meeting_date',
        'start_time',
        'end_time',
        'location',
        'online_link',
        'status',
        'recruitment_request_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'meeting_date' => 'date',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recruitmentRequest()
    {
        return $this->belongsTo(RecruitmentRequest::class);
    }

    public function participants()
    {
        return $this->hasMany(MeetingParticipant::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'meeting_participants')
            ->withPivot(['notification_read_at', 'reminder_sent_at'])
            ->withTimestamps();
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereHas('participants', fn (Builder $participantQuery) => $participantQuery
            ->where('user_id', $user->id));
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_SCHEDULED => 'Planifiee',
            self::STATUS_COMPLETED => 'Terminee',
            self::STATUS_CANCELLED => 'Annulee',
        ];
    }
}
