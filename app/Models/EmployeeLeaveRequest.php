<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeLeaveRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'leave_type',
        'start_date',
        'end_date',
        'reason',
        'status',
        'admin_notes',
        'admin_seen_at',
        'decided_at',
        'decided_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'admin_seen_at' => 'datetime',
        'decided_at' => 'datetime',
    ];

    public static function availableTypes(): array
    {
        return [
            'annual' => 'Conge annuel',
            'sick' => 'Conge maladie',
            'personal' => 'Conge personnel',
            'unpaid' => 'Conge sans solde',
        ];
    }

    public static function availableStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'En attente',
            self::STATUS_APPROVED => 'Approuve',
            self::STATUS_REJECTED => 'Rejete',
            self::STATUS_CANCELLED => 'Annule',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function decider()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
