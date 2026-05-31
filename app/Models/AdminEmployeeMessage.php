<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminEmployeeMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'attachment_path',
        'attachment_original_name',
        'attachment_mime_type',
        'attachment_size',
        'delivered_at',
        'seen_at',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'seen_at' => 'datetime',
    ];

    public function conversation()
    {
        return $this->belongsTo(AdminEmployeeConversation::class, 'conversation_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function isImageAttachment(): bool
    {
        return str_starts_with((string) $this->attachment_mime_type, 'image/');
    }

    public function isVideoAttachment(): bool
    {
        return str_starts_with((string) $this->attachment_mime_type, 'video/');
    }

    public function isPdfAttachment(): bool
    {
        return (string) $this->attachment_mime_type === 'application/pdf';
    }

    public function isWordAttachment(): bool
    {
        $extension = strtolower(pathinfo((string) $this->attachment_original_name, PATHINFO_EXTENSION));

        return $extension === 'docx'
            || (string) $this->attachment_mime_type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    }

    public function isSpreadsheetAttachment(): bool
    {
        $extension = strtolower(pathinfo((string) $this->attachment_original_name, PATHINFO_EXTENSION));

        return $extension === 'xlsx'
            || (string) $this->attachment_mime_type === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }

    public function attachmentTypeLabel(): string
    {
        if ($this->isImageAttachment()) {
            return 'Image';
        }

        if ($this->isVideoAttachment()) {
            return 'Video';
        }

        if ($this->isPdfAttachment()) {
            return 'PDF';
        }

        if ($this->isWordAttachment()) {
            return 'DOCX';
        }

        if ($this->isSpreadsheetAttachment()) {
            return 'XLSX';
        }

        $extension = pathinfo((string) $this->attachment_original_name, PATHINFO_EXTENSION);

        return $extension ? strtoupper($extension) : 'Fichier';
    }

    public function attachmentSizeForHumans(): ?string
    {
        $bytes = (int) $this->attachment_size;

        if ($bytes <= 0) {
            return null;
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1, ',', ' ') . ' Mo';
        }

        return number_format(max(1, $bytes / 1024), 0, ',', ' ') . ' Ko';
    }

    public function canBeDeletedBy(?User $user): bool
    {
        return $user !== null
            && (int) $this->sender_id === (int) $user->id
            && $this->created_at !== null
            && $this->created_at->greaterThanOrEqualTo(now()->subHours(3));
    }
}
