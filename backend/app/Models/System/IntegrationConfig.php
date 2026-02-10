<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class IntegrationConfig extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'description_ar',
        'provider',
        'endpoint',
        'credentials',
        'settings',
        'is_active',
        'last_sync_at',
        'last_sync_status',
        'last_sync_message',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    protected $hidden = [
        'credentials',
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

    public function getDecryptedCredentialsAttribute(): ?array
    {
        if (!$this->credentials) {
            return null;
        }

        try {
            return json_decode(Crypt::decryptString($this->credentials), true);
        } catch (\Exception $e) {
            return null;
        }
    }

    // =============================================================================
    // Mutators
    // =============================================================================

    public function setCredentialsAttribute($value): void
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }

        $this->attributes['credentials'] = Crypt::encryptString($value);
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    // =============================================================================
    // Methods
    // =============================================================================

    public function updateSyncStatus(string $status, ?string $message = null): bool
    {
        return $this->update([
            'last_sync_at' => now(),
            'last_sync_status' => $status,
            'last_sync_message' => $message,
        ]);
    }
}
