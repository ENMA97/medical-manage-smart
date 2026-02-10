<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasUuids;

    // Immutable - no updated_at
    public $timestamps = true;
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'user_name',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'url',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // =============================================================================
    // Constants
    // =============================================================================

    public const EVENT_CREATED = 'created';
    public const EVENT_UPDATED = 'updated';
    public const EVENT_DELETED = 'deleted';
    public const EVENT_LOGIN = 'login';
    public const EVENT_LOGOUT = 'logout';
    public const EVENT_APPROVED = 'approved';
    public const EVENT_REJECTED = 'rejected';
    public const EVENT_EXPORTED = 'exported';

    // =============================================================================
    // Relationships
    // =============================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForEntity($query, string $entityType, ?string $entityId = null)
    {
        $query->where('auditable_type', $entityType);

        if ($entityId) {
            $query->where('auditable_id', $entityId);
        }

        return $query;
    }

    public function scopeByEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // =============================================================================
    // Static Methods
    // =============================================================================

    public static function log(
        string $event,
        $auditable,
        ?array $oldValues = null,
        ?array $newValues = null
    ): self {
        $user = auth()->user();

        return self::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System',
            'event' => $event,
            'auditable_type' => get_class($auditable),
            'auditable_id' => $auditable->id ?? null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
        ]);
    }
}
