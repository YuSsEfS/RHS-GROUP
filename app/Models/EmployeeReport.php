<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeReport extends Model
{
    public const TYPE_DAILY = 'daily';
    public const TYPE_WEEKLY = 'weekly';

    public const STATUS_PENDING = 'pending';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_VALIDATED = 'validated';

    protected $fillable = [
        'user_id',
        'report_type',
        'report_date',
        'title',
        'summary',
        'achievements',
        'blockers',
        'next_steps',
        'status',
        'attachment_path',
        'admin_notes',
        'admin_seen_at',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'report_date' => 'date',
        'admin_seen_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public static function availableTypes(): array
    {
        return [
            self::TYPE_DAILY => 'Quotidien',
            self::TYPE_WEEKLY => 'Hebdomadaire',
        ];
    }

    public static function availableStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'En attente',
            self::STATUS_REVIEWED => 'Consulte',
            self::STATUS_VALIDATED => 'Valide',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
