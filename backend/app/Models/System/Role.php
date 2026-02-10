<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'description_ar',
        'description_en',
        'is_system',
        'is_active',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    // =============================================================================
    // Accessors
    // =============================================================================

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : ($this->name_en ?: $this->name_ar);
    }

    public function getDescriptionAttribute(): ?string
    {
        return app()->getLocale() === 'ar' ? $this->description_ar : ($this->description_en ?: $this->description_ar);
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')
            ->withPivot('assigned_by', 'assigned_at');
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeNonSystem($query)
    {
        return $query->where('is_system', false);
    }
}
