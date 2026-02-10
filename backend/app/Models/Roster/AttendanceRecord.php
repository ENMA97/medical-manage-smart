<?php

namespace App\Models\Roster;

use App\Models\HR\Employee;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory, HasUuids;

    /**
     * أنواع التسجيل
     */
    public const TYPE_CHECK_IN = 'check_in';
    public const TYPE_CHECK_OUT = 'check_out';
    public const TYPE_BREAK_START = 'break_start';
    public const TYPE_BREAK_END = 'break_end';

    public const TYPES = [
        self::TYPE_CHECK_IN => 'حضور',
        self::TYPE_CHECK_OUT => 'انصراف',
        self::TYPE_BREAK_START => 'بداية استراحة',
        self::TYPE_BREAK_END => 'نهاية استراحة',
    ];

    /**
     * مصادر التسجيل
     */
    public const SOURCE_DEVICE = 'device';
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_MOBILE = 'mobile';

    public const SOURCES = [
        self::SOURCE_DEVICE => 'جهاز بصمة',
        self::SOURCE_MANUAL => 'يدوي',
        self::SOURCE_MOBILE => 'جوال',
    ];

    // جدول غير قابل للتعديل (immutable) - فقط created_at
    public $timestamps = true;
    const UPDATED_AT = null;

    protected $fillable = [
        'employee_id',
        'device_id',
        'type',
        'punched_at',
        'source',
        'latitude',
        'longitude',
        'location_name',
        'device_serial',
        'is_valid',
        'notes',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'punched_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_valid' => 'boolean',
        'processed_at' => 'datetime',
    ];

    // =============================================================================
    // Accessors
    // =============================================================================

    public function getSourceNameAttribute(): string
    {
        return self::SOURCES[$this->source] ?? $this->source;
    }

    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(BiometricDevice::class, 'device_id');
    }

    public function processedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'processed_by');
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    public function scopeCheckIns($query)
    {
        return $query->where('type', self::TYPE_CHECK_IN);
    }

    public function scopeCheckOuts($query)
    {
        return $query->where('type', self::TYPE_CHECK_OUT);
    }

    public function scopeValid($query)
    {
        return $query->where('is_valid', true);
    }

    public function scopeFromDevice($query)
    {
        return $query->where('source', self::SOURCE_DEVICE);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('punched_at', $date);
    }

    public function scopeForEmployee($query, string $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    // =============================================================================
    // Methods
    // =============================================================================

    /**
     * معالجة السجل
     */
    public function process(string $processedBy, ?string $notes = null): bool
    {
        $this->processed_by = $processedBy;
        $this->processed_at = now();
        if ($notes) {
            $this->notes = $notes;
        }
        return $this->save();
    }

    /**
     * التحقق من الموقع الجغرافي
     */
    public function isWithinLocation(float $targetLat, float $targetLng, float $radiusMeters): bool
    {
        if (!$this->latitude || !$this->longitude) {
            return false;
        }

        $distance = $this->calculateDistance(
            $this->latitude,
            $this->longitude,
            $targetLat,
            $targetLng
        );

        return $distance <= $radiusMeters;
    }

    /**
     * حساب المسافة بين نقطتين (بالأمتار)
     */
    protected function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // meters

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLng / 2) * sin($deltaLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
