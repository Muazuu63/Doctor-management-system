<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'name_bn',
        'email',
        'phone',
        'password',
        'role',
        'is_active',
        'two_factor_enabled',
        'two_factor_method',
        'two_factor_secret',
        'two_factor_confirmed_at',
        'failed_login_attempts',
        'locked_until',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'admin'], true);
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function canManageUsers(): bool
    {
        return $this->isSuperAdmin();
    }

    public function canManageSettings(): bool
    {
        return $this->isAdmin();
    }

    public function canManageSms(): bool
    {
        return $this->isAdmin();
    }

    public function canPrescribe(): bool
    {
        return $this->isAdmin();
    }

    public function canManageQueue(): bool
    {
        return true;
    }

    public function displayName(): string
    {
        return $this->name_bn ?: $this->name;
    }

    public function roleLabel(): string
    {
        return config('clinic.roles')[$this->role] ?? $this->role;
    }

    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }
}
