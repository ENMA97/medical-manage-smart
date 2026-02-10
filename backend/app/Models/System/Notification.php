<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'type',
        'title_ar',
        'title_en',
        'body_ar',
        'body_en',
        'data',
        'action_url',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    // =============================================================================
    // Accessors
    // =============================================================================

    public function getTitleAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->title_ar : ($this->title_en ?: $this->title_ar);
    }

    public function getBodyAttribute(): ?string
    {
        return app()->getLocale() === 'ar' ? $this->body_ar : ($this->body_en ?: $this->body_ar);
    }

    public function getIsReadAttribute(): bool
    {
        return $this->read_at !== null;
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // =============================================================================
    // Methods
    // =============================================================================

    public function markAsRead(): bool
    {
        if ($this->read_at) {
            return true;
        }

        return $this->update(['read_at' => now()]);
    }

    // =============================================================================
    // Static Methods
    // =============================================================================

    public static function send(
        string $userId,
        string $type,
        string $titleAr,
        ?string $titleEn = null,
        ?string $bodyAr = null,
        ?string $bodyEn = null,
        ?array $data = null,
        ?string $actionUrl = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'type' => $type,
            'title_ar' => $titleAr,
            'title_en' => $titleEn,
            'body_ar' => $bodyAr,
            'body_en' => $bodyEn,
            'data' => $data,
            'action_url' => $actionUrl,
        ]);
    }
}
