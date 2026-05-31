<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RhResource extends Model
{
    protected $fillable = [
        'title',
        'description',
        'category',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'visibility_roles',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'visibility_roles' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeVisibleFor(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query
            ->where('is_active', true)
            ->where(function (Builder $visibilityQuery) use ($user) {
                $visibilityQuery
                    ->whereNull('visibility_roles')
                    ->orWhereJsonContains('visibility_roles', $user->role);
            });
    }

    public static function categories(): array
    {
        return [
            'procedures' => 'Procedures',
            'documents' => 'Documents administratifs',
            'formation' => 'Formation interne',
            'paie' => 'Paie et avantages',
            'general' => 'General',
        ];
    }
}
