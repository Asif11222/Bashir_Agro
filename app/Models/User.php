<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'phone', 'password', 'role', 'is_active', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(TaskAssignment::class, 'assigned_by');
    }

    /**
     * Linked farmer profile for mobile farmer accounts.
     */
    public function farmerProfile(): HasOne
    {
        return $this->hasOne(Farmer::class, 'user_id');
    }

    /**
     * Check if user is system owner.
     */
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    /**
     * Check if user is admin or owner.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'owner'], true);
    }

    /**
     * Check if user has farmer role.
     */
    public function isFarmer(): bool
    {
        return $this->role === 'farmer';
    }

    /**
     * Check if user has specific role or one of given roles.
     */
    public function hasRole(string|array $roles): bool
    {
        return in_array($this->role, (array) $roles, true);
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        $rolePermissions = [
            'admin' => [
                'farmers.view',
                'farmers.create',
                'farmers.update',
                'farmers.delete',
                'farmers.manage',
                'locations.manage',
                'tasks.create',
                'tasks.assign',
                'assignment.create',
                'reports.view',
                'settings.manage',
            ],
            'manager' => [
                'farmers.view',
                'farmers.create',
                'farmers.update',
                'locations.manage',
                'tasks.create',
                'tasks.assign',
                'reports.view',
            ],
            'supervisor' => [
                'farmers.view',
                'tasks.assign',
                'reports.view',
            ],
            'farmer' => [
                'task.view',
                'task.submit',
                'profile.view',
            ],
        ];

        $permissions = $rolePermissions[$this->role] ?? [];

        return in_array($permission, $permissions, true);
    }
}
