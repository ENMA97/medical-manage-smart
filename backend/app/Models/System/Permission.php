<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'code',
        'module',
        'name_ar',
        'name_en',
        'description_ar',
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
        return $this->description_ar;
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }
}
