<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientRequestAlert extends Model
{
    public const STATUS_NEW = 'new';
    public const STATUS_VIEWED = 'viewed';
    public const STATUS_PROCESSED = 'processed';

    protected $fillable = [
        'recruitment_request_id',
        'client_user_id',
        'message',
        'status',
        'admin_response',
        'admin_seen_at',
        'employee_seen_at',
        'responded_at',
        'responded_by',
    ];

    protected $casts = [
        'admin_seen_at' => 'datetime',
        'employee_seen_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public static function availableStatuses(): array
    {
        return [
            self::STATUS_NEW => 'Nouvelle',
            self::STATUS_VIEWED => 'Vue',
            self::STATUS_PROCESSED => 'Traitee',
        ];
    }

    public static function quickResponses(): array
    {
        return [
            'Votre demande est en cours de traitement.',
            'Notre equipe revient vers vous tres prochainement.',
            'Le matching est en cours.',
            'Nous avons besoin d informations complementaires.',
            'Votre demande a ete traitee.',
        ];
    }

    public function recruitmentRequest()
    {
        return $this->belongsTo(RecruitmentRequest::class);
    }

    public function clientUser()
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function responder()
    {
        return $this->belongsTo(User::class, 'responded_by');
    }
}
