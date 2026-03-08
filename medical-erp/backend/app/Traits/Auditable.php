<?php

namespace App\Traits;

use App\Models\AuditLog;

trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(function ($model) {
            static::logAudit($model, 'created', [], $model->getAttributes());
        });

        static::updated(function ($model) {
            $old = $model->getOriginal();
            $new = $model->getChanges();

            // Remove timestamps from diff
            unset($new['updated_at'], $old['updated_at']);

            if (!empty($new)) {
                $oldFiltered = array_intersect_key($old, $new);
                static::logAudit($model, 'updated', $oldFiltered, $new);
            }
        });

        static::deleted(function ($model) {
            static::logAudit($model, 'deleted', $model->getAttributes(), []);
        });
    }

    private static function logAudit($model, string $event, array $oldValues, array $newValues): void
    {
        $user = auth()->user();

        // Don't log during seeding or CLI without auth
        if (!$user && app()->runningInConsole()) {
            return;
        }

        AuditLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->full_name ?? 'System',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'created_at' => now(),
        ]);
    }
}
