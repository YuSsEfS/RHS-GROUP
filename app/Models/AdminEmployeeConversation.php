<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class AdminEmployeeConversation extends Model
{
    public const TYPE_DIRECT = 'direct';
    public const TYPE_GROUP = 'group';

    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_URGENT = 'urgent';

    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'admin_user_id',
        'employee_user_id',
        'conversation_type',
        'group_name',
        'subject',
        'priority',
        'status',
        'last_message_at',
        'admin_seen_at',
        'employee_seen_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'admin_seen_at' => 'datetime',
        'employee_seen_at' => 'datetime',
    ];

    public static function availablePriorities(): array
    {
        return [
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_URGENT => 'Urgent',
        ];
    }

    public static function availableStatuses(): array
    {
        return [
            self::STATUS_OPEN => 'Ouverte',
            self::STATUS_CLOSED => 'Cloturee',
        ];
    }

    public function adminUser()
    {
        return $this->participantOneUser();
    }

    public function employeeUser()
    {
        return $this->participantTwoUser();
    }

    public function participantOneUser()
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function participantTwoUser()
    {
        return $this->belongsTo(User::class, 'employee_user_id');
    }

    public function messages()
    {
        return $this->hasMany(AdminEmployeeMessage::class, 'conversation_id')
            ->orderBy('created_at')
            ->orderBy('id');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'admin_employee_conversation_participants', 'conversation_id', 'user_id')
            ->withPivot('seen_at')
            ->withTimestamps();
    }

    public function latestMessage()
    {
        return $this->hasOne(AdminEmployeeMessage::class, 'conversation_id')->latestOfMany();
    }

    public function scopeForParticipant(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->id : (int) $user;

        return $query->where(function (Builder $builder) use ($userId) {
            $builder->where('admin_user_id', $userId)
                ->orWhere('employee_user_id', $userId);

            if (Schema::hasTable('admin_employee_conversation_participants')) {
                $builder->orWhereHas('participants', fn (Builder $participantQuery) => $participantQuery->whereKey($userId));
            }
        });
    }

    public function isParticipant(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if (
            (int) $this->admin_user_id === (int) $user->id
            || (int) $this->employee_user_id === (int) $user->id
        ) {
            return true;
        }

        if ($this->relationLoaded('participants')) {
            return $this->participants->contains('id', $user->id);
        }

        return $this->participants()->whereKey($user->id)->exists();
    }

    public function isGroup(): bool
    {
        return $this->conversation_type === self::TYPE_GROUP;
    }

    public function isParticipantOne(?User $user): bool
    {
        return $user !== null && (int) $this->admin_user_id === (int) $user->id;
    }

    public function isParticipantTwo(?User $user): bool
    {
        return $user !== null && (int) $this->employee_user_id === (int) $user->id;
    }

    public function otherParticipantFor(?User $user): ?User
    {
        if ($this->isParticipantOne($user)) {
            return $this->relationLoaded('participantTwoUser')
                ? $this->participantTwoUser
                : $this->participantTwoUser()->first();
        }

        if ($this->isParticipantTwo($user)) {
            return $this->relationLoaded('participantOneUser')
                ? $this->participantOneUser
                : $this->participantOneUser()->first();
        }

        return null;
    }

    public function displayNameFor(?User $user): string
    {
        if ($this->isGroup()) {
            return $this->group_name ?: $this->subject ?: 'Groupe RHS';
        }

        return $this->otherParticipantFor($user)?->name ?: ($this->subject ?: 'Conversation RHS');
    }

    public function displaySubtitleFor(?User $user): string
    {
        if ($this->isGroup()) {
            $count = $this->relationLoaded('participants')
                ? $this->participants->count()
                : $this->participants()->count();

            return 'Groupe - ' . $count . ' participant' . ($count > 1 ? 's' : '');
        }

        $other = $this->otherParticipantFor($user);

        if (!$other) {
            return '-';
        }

        return $other->hasRole(User::ROLE_ADMIN) ? 'Administration RHS' : 'Collaborateur RHS';
    }

    public function avatarInitialFor(?User $user): string
    {
        return strtoupper(mb_substr($this->displayNameFor($user), 0, 1, 'UTF-8'));
    }

    public function seenColumnFor(User $user): ?string
    {
        if ($this->isParticipantOne($user)) {
            return 'admin_seen_at';
        }

        if ($this->isParticipantTwo($user)) {
            return 'employee_seen_at';
        }

        return null;
    }

    public function otherSeenColumnFor(User $user): ?string
    {
        if ($this->isParticipantOne($user)) {
            return 'employee_seen_at';
        }

        if ($this->isParticipantTwo($user)) {
            return 'admin_seen_at';
        }

        return null;
    }

    public function unreadFor(?User $user): bool
    {
        if (!$user || !$this->last_message_at) {
            return false;
        }

        if ($this->isGroup()) {
            $seenAt = $this->participantSeenAt($user);

            return $seenAt === null || $this->last_message_at->gt($seenAt);
        }

        $seenColumn = $this->seenColumnFor($user);

        if (!$seenColumn) {
            return false;
        }

        $seenAt = $this->{$seenColumn};

        return $seenAt === null || $this->last_message_at->gt($seenAt);
    }

    public function unreadMessagesCountFor(?User $user): int
    {
        if (!$user) {
            return 0;
        }

        if ($this->isGroup()) {
            $seenAt = $this->participantSeenAt($user);

            return $this->messages()
                ->where('sender_id', '!=', $user->id)
                ->when($seenAt, fn (Builder $query) => $query->where('created_at', '>', $seenAt))
                ->count();
        }

        $seenColumn = $this->seenColumnFor($user);

        if (!$seenColumn) {
            return 0;
        }

        return $this->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('seen_at')
            ->count();
    }

    public function unreadForAdmin(): bool
    {
        return $this->last_message_at !== null
            && ($this->admin_seen_at === null || $this->last_message_at->gt($this->admin_seen_at));
    }

    public function unreadForEmployee(): bool
    {
        return $this->last_message_at !== null
            && ($this->employee_seen_at === null || $this->last_message_at->gt($this->employee_seen_at));
    }

    public function markSeenFor(User $user): void
    {
        $seenAt = now();

        if ($this->isGroup()) {
            $currentSeenAt = $this->participantSeenAt($user);

            if ($currentSeenAt && $this->last_message_at && !$this->last_message_at->gt($currentSeenAt)) {
                return;
            }

            $this->participants()->syncWithoutDetaching([
                $user->id => ['seen_at' => $seenAt],
            ]);

            return;
        }

        $seenColumn = $this->seenColumnFor($user);

        if (!$seenColumn) {
            return;
        }

        $currentSeenAt = $this->{$seenColumn};

        if ($currentSeenAt && $this->last_message_at && !$this->last_message_at->gt($currentSeenAt)) {
            return;
        }

        $this->forceFill([$seenColumn => $seenAt])->save();

        if (Schema::hasTable('admin_employee_conversation_participants')) {
            $this->participants()->syncWithoutDetaching([
                $user->id => ['seen_at' => $seenAt],
            ]);
        }
    }

    public function syncAfterOutgoingMessage(User $sender): void
    {
        if ($this->isGroup()) {
            $this->forceFill(['last_message_at' => now()])->save();
            $this->participants()->syncWithoutDetaching([
                $sender->id => ['seen_at' => now()],
            ]);

            return;
        }

        $seenColumn = $this->seenColumnFor($sender);
        $otherSeenColumn = $this->otherSeenColumnFor($sender);
        $updates = [
            'last_message_at' => now(),
        ];

        if ($seenColumn) {
            $updates[$seenColumn] = now();
        }

        if ($otherSeenColumn) {
            $updates[$otherSeenColumn] = null;
        }

        $this->forceFill($updates)->save();

        if (Schema::hasTable('admin_employee_conversation_participants')) {
            $sync = [
                $sender->id => ['seen_at' => $updates[$seenColumn] ?? now()],
            ];

            $otherUserId = $this->isParticipantOne($sender)
                ? (int) $this->employee_user_id
                : (int) $this->admin_user_id;

            if ($otherUserId > 0) {
                $sync[$otherUserId] = ['seen_at' => null];
            }

            $this->participants()->syncWithoutDetaching($sync);
        }
    }

    private function participantSeenAt(User $user)
    {
        if ($this->relationLoaded('participants')) {
            $participant = $this->participants->firstWhere('id', $user->id);

            $seenAt = $participant?->pivot?->seen_at;

            return $seenAt ? Carbon::parse($seenAt) : null;
        }

        $seenAt = $this->participants()
            ->whereKey($user->id)
            ->first()
            ?->pivot
            ?->seen_at;

        return $seenAt ? Carbon::parse($seenAt) : null;
    }
}
