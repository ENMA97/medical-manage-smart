<?php

namespace App\Models\Roster;

use App\Models\HR\Department;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BiometricDevice extends Model
{
    use HasFactory, HasUuids;

    /**
     * حالات الجهاز
     */
    public const STATUS_ONLINE = 'online';
    public const STATUS_OFFLINE = 'offline';
    public const STATUS_MAINTENANCE = 'maintenance';

    public const STATUSES = [
        self::STATUS_ONLINE => 'متصل',
        self::STATUS_OFFLINE => 'غير متصل',
        self::STATUS_MAINTENANCE => 'صيانة',
    ];

    protected $fillable = [
        'serial_number',
        'name',
        'model',
        'manufacturer',
        'ip_address',
        'port',
        'location',
        'department_id',
        'status',
        'is_online',
        'last_sync_at',
        'last_sync_records',
        'is_active',
        'connection_settings',
    ];

    protected $casts = [
        'port' => 'integer',
        'last_sync_at' => 'datetime',
        'last_sync_records' => 'integer',
        'is_online' => 'boolean',
        'is_active' => 'boolean',
        'connection_settings' => 'array',
    ];

    // =============================================================================
    // Accessors
    // =============================================================================

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getConnectionStringAttribute(): string
    {
        return "{$this->ip_address}:{$this->port}";
    }

    public function getLastSyncAgoAttribute(): ?string
    {
        if (!$this->last_sync_at) {
            return null;
        }

        return $this->last_sync_at->diffForHumans();
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'device_id');
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOnline($query)
    {
        return $query->where('is_online', true)->orWhere('status', self::STATUS_ONLINE);
    }

    public function scopeOffline($query)
    {
        return $query->where('is_online', false)->orWhere('status', self::STATUS_OFFLINE);
    }

    public function scopeNeedSync($query, int $minutesSinceLastSync = 60)
    {
        return $query->where(function ($q) use ($minutesSinceLastSync) {
            $q->whereNull('last_sync_at')
                ->orWhere('last_sync_at', '<', now()->subMinutes($minutesSinceLastSync));
        });
    }

    // =============================================================================
    // Methods
    // =============================================================================

    /**
     * تحديث حالة الاتصال
     */
    public function updateOnlineStatus(bool $isOnline): bool
    {
        return $this->update([
            'is_online' => $isOnline,
            'status' => $isOnline ? self::STATUS_ONLINE : self::STATUS_OFFLINE,
        ]);
    }

    /**
     * تسجيل نجاح المزامنة
     */
    public function recordSync(int $recordsCount): bool
    {
        return $this->update([
            'last_sync_at' => now(),
            'last_sync_records' => $recordsCount,
            'is_online' => true,
            'status' => self::STATUS_ONLINE,
        ]);
    }
}
