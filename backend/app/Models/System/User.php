<?php

namespace App\Models\System;

use App\Models\HR\Employee;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, HasUuids, HasApiTokens, Notifiable, SoftDeletes;

    protected $fillable = [
        'username',
        'email',
        'password',
        'name_ar',
        'name_en',
        'phone',
        'employee_id',
        'is_active',
        'must_change_password',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'must_change_password' => 'boolean',
        'last_login_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    // =============================================================================
    // Accessors
    // =============================================================================

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : ($this->name_en ?: $this->name_ar);
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot('assigned_by', 'assigned_at')
            ->withTimestamps();
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // =============================================================================
    // Authorization Helpers
    // =============================================================================

    public function hasPermission(string $permissionCode): bool
    {
        return $this->roles()
            ->whereHas('permissions', fn($q) => $q->where('code', $permissionCode))
            ->exists();
    }

    public function hasRole(string $roleCode): bool
    {
        return $this->roles()->where('code', $roleCode)->exists();
    }

    public function getAllPermissions(): array
    {
        return $this->roles()
            ->with('permissions')
            ->get()
            ->flatMap->permissions
            ->pluck('code')
            ->unique()
            ->values()
            ->toArray();
    }
}
