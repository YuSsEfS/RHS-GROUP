<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_EMPLOYEE = 'employee';
    public const ROLE_SUPERVISOR = 'supervisor';
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
        'profile_photo_path',
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

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (empty($this->profile_photo_path)) {
            return null;
        }

        return route('public.file', ltrim($this->profile_photo_path, '/'));
    }

    public function isAdmin(): bool
    {
        return $this->currentRole() === self::ROLE_ADMIN;
    }

    public function hasRole(string $role): bool
    {
        return $this->currentRole() === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->currentRole(), $roles, true);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function permissionKeys(): array
    {
        $snapshot = $this->permissionSnapshot();
        $role = $snapshot['role'] ?? $this->role;
        $permissions = $snapshot['permissions'] ?? $this->permissions;
        $allowed = static::allowedPermissionsForRole($role);

        if (is_array($permissions)) {
            $keys = array_values(array_unique(array_filter($permissions, static fn ($value) => is_string($value) && $value !== '')));

            if ($allowed === ['*']) {
                return $keys;
            }

            return array_values(array_intersect($keys, $allowed));
        }

        return static::defaultPermissionsForRole($role);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return in_array($permission, $this->permissionKeys(), true);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $granted = $this->permissionKeys();

        foreach ($permissions as $permission) {
            if (in_array($permission, $granted, true)) {
                return true;
            }
        }

        return false;
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
            self::ROLE_SUPERVISOR => 'Superviseur',
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
            'recruitment_requests' => 'Demandes de recrutement',
            'recruitment_assignments_view' => 'Voir demandes assignees',
            'recruitment_assignments_update' => 'Mettre a jour progression recrutement',
            'cv_bank' => 'Acces CV Bank',
            'cv_bank_manage' => 'Gestion CV Bank',
            'external_cvs' => 'Acces base externe CV',
            'external_cvs_manage' => 'Gestion base externe CV',
            'client_alerts_view' => 'Voir relances clients',
            'meetings_view' => 'Voir reunions',
            'meetings_manage' => 'Gerer reunions',
            'rh_resources_view' => 'Voir ressources RH',
            'rh_resources_manage' => 'Gerer ressources RH',
            'employee_reports' => 'Rapports employes',
            'employee_leave_requests' => 'Demandes de conge',
            'employee_internal_requests' => 'Demandes RH internes',
            'admin_employee_messages' => 'Messagerie admin / employes',
        ];
    }

    public static function permissionRoleMap(): array
    {
        return [
            'recruitment_requests' => [self::ROLE_EMPLOYEE, self::ROLE_SUPERVISOR, self::ROLE_CLIENT],
            'recruitment_assignments_view' => [self::ROLE_EMPLOYEE, self::ROLE_SUPERVISOR],
            'recruitment_assignments_update' => [self::ROLE_EMPLOYEE, self::ROLE_SUPERVISOR],
            'cv_bank' => [self::ROLE_EMPLOYEE, self::ROLE_SUPERVISOR],
            'cv_bank_manage' => [self::ROLE_EMPLOYEE, self::ROLE_SUPERVISOR],
            'external_cvs' => [self::ROLE_EMPLOYEE, self::ROLE_SUPERVISOR],
            'external_cvs_manage' => [self::ROLE_EMPLOYEE, self::ROLE_SUPERVISOR],
            'client_alerts_view' => [self::ROLE_EMPLOYEE, self::ROLE_SUPERVISOR],
            'meetings_view' => [self::ROLE_EMPLOYEE, self::ROLE_SUPERVISOR],
            'meetings_manage' => [self::ROLE_SUPERVISOR],
            'rh_resources_view' => [self::ROLE_EMPLOYEE, self::ROLE_SUPERVISOR],
            'rh_resources_manage' => [self::ROLE_SUPERVISOR],
            'employee_reports' => [self::ROLE_EMPLOYEE, self::ROLE_SUPERVISOR],
            'employee_leave_requests' => [self::ROLE_EMPLOYEE, self::ROLE_SUPERVISOR],
            'employee_internal_requests' => [self::ROLE_EMPLOYEE, self::ROLE_SUPERVISOR],
            'admin_employee_messages' => [self::ROLE_EMPLOYEE, self::ROLE_SUPERVISOR],
        ];
    }

    public static function defaultPermissionsForRole(?string $role): array
    {
        return match ($role) {
            self::ROLE_EMPLOYEE,
            self::ROLE_SUPERVISOR => [
                'employee_reports',
                'employee_leave_requests',
                'employee_internal_requests',
                'admin_employee_messages',
                'meetings_view',
                'rh_resources_view',
            ],
            self::ROLE_CLIENT => [
                'recruitment_requests',
            ],
            default => [],
        };
    }

    public static function allowedPermissionsForRole(?string $role): array
    {
        return match ($role) {
            self::ROLE_ADMIN => ['*'],
            self::ROLE_EMPLOYEE,
            self::ROLE_SUPERVISOR,
            self::ROLE_CLIENT => array_keys(array_filter(
                self::permissionRoleMap(),
                static fn (array $roles): bool => in_array($role, $roles, true)
            )),
            default => [],
        };
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

    public function assignedRecruitmentRequests()
    {
        return $this->hasMany(RecruitmentRequest::class, 'assigned_employee_id');
    }

    public function adminConversations()
    {
        return $this->hasMany(AdminEmployeeConversation::class, 'admin_user_id');
    }

    public function employeeConversations()
    {
        return $this->hasMany(AdminEmployeeConversation::class, 'employee_user_id');
    }

    public function meetingParticipations()
    {
        return $this->hasMany(MeetingParticipant::class);
    }

    public function rhResources()
    {
        return $this->hasMany(RhResource::class, 'created_by');
    }

    private function currentRole(): ?string
    {
        return $this->permissionSnapshot()['role'] ?? $this->role;
    }

    private function permissionSnapshot(): array
    {
        static $snapshots = [];

        if (!$this->exists || !$this->id) {
            return [
                'role' => $this->role,
                'permissions' => $this->permissions,
            ];
        }

        if (!array_key_exists($this->id, $snapshots)) {
            $fresh = static::query()
                ->whereKey($this->id)
                ->first(['role', 'permissions']);

            $snapshots[$this->id] = [
                'role' => $fresh?->role ?? $this->role,
                'permissions' => $fresh?->permissions ?? $this->permissions,
            ];
        }

        return $snapshots[$this->id];
    }
}
