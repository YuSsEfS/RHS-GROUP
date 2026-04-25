<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeInternalRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'category',
        'subject',
        'message',
        'status',
        'admin_notes',
        'admin_seen_at',
        'responded_at',
        'responded_by',
    ];

    protected $casts = [
        'admin_seen_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public static function availableCategories(): array
    {
        return [
            'attestation_salaire' => 'Attestation de salaire',
            'remboursement' => 'Remboursement',
            'question_rh' => 'Question RH',
            'contrat' => 'Contrat',
            'autre' => 'Autre',
        ];
    }

    public static function availableStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Nouvelle',
            self::STATUS_IN_PROGRESS => 'En cours',
            self::STATUS_RESOLVED => 'Traitee',
            self::STATUS_REJECTED => 'Rejetee',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function responder()
    {
        return $this->belongsTo(User::class, 'responded_by');
    }
}
