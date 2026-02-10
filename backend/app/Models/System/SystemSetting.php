<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'name_ar',
        'name_en',
        'description_ar',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
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

    public function getTypedValueAttribute()
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($this->value) ? (float) $this->value : null,
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    // =============================================================================
    // Static Methods
    // =============================================================================

    public static function getValue(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->typed_value : $default;
    }

    public static function setValue(string $key, $value): bool
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return false;
        }

        if ($setting->type === 'json' && is_array($value)) {
            $value = json_encode($value);
        }

        return $setting->update(['value' => $value]);
    }
}
