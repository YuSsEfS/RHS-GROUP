<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_EMPLOYEE = 'employee';
    public const ROLE_CLIENT = 'client';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'permissions',
        'approved_at',
        'approved_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
            'approved_at' => 'datetime',
        ];
    }

    public function approver()
    {
        return $this->belongsTo(self::class, 'approved_by');
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->hasRole(self::ROLE_EMPLOYEE) && in_array($permission, [
            'employee_reports',
            'employee_leave_requests',
            'employee_internal_requests',
        ], true)) {
            return true;
        }

        return in_array($permission, $this->permissions ?? [], true);
    }

    public function approve(?self $approver = null): void
    {
        $this->forceFill([
            'status' => self::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $approver?->id,
        ])->save();
    }

    public function reject(?self $approver = null): void
    {
        $this->forceFill([
            'status' => self::STATUS_REJECTED,
            'approved_at' => null,
            'approved_by' => $approver?->id,
        ])->save();
    }

    public static function availableRoles(): array
    {
        return [
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_EMPLOYEE => 'Employe',
            self::ROLE_CLIENT => 'Client',
        ];
    }

    public static function availableStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'En attente',
            self::STATUS_APPROVED => 'Approuve',
            self::STATUS_REJECTED => 'Rejete',
        ];
    }

    public static function availablePermissions(): array
    {
        return [
            'cv_bank' => 'Banque CV',
            'recruitment_requests' => 'Demandes de recrutement',
            'clients_management' => 'Gestion des clients',
            'client_records' => 'Fiches clients specifiques',
            'job_offers' => 'Offres d emploi',
            'formations' => 'Formations',
            'messages' => 'Messages',
            'employee_reports' => 'Rapports employes',
            'employee_leave_requests' => 'Demandes de conge',
            'employee_internal_requests' => 'Demandes RH internes',
        ];
    }

    public function cvFolders()
    {
        return $this->hasMany(CvFolder::class, 'created_by');
    }

    public function externalCvBatches()
    {
        return $this->hasMany(ExternalCvBatch::class, 'created_by');
    }

    public function employeeReports()
    {
        return $this->hasMany(EmployeeReport::class);
    }

    public function employeeLeaveRequests()
    {
        return $this->hasMany(EmployeeLeaveRequest::class);
    }

    public function employeeInternalRequests()
    {
        return $this->hasMany(EmployeeInternalRequest::class);
    }

    public function clientRequestAlerts()
    {
        return $this->hasMany(ClientRequestAlert::class, 'client_user_id');
    }
}
