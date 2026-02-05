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

    /**
     * مصادر التسجيل
     */
    public const SOURCE_BIOMETRIC = 'biometric';
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_MOBILE = 'mobile';
    public const SOURCE_WEB = 'web';

    // جدول غير قابل للتعديل (immutable)
    public $timestamps = true;
    const UPDATED_AT = null;

    protected $fillable = [
        'employee_id',
        'roster_assignment_id',
        'record_type',
        'record_time',
        'source',
        'biometric_device_id',
        'location_latitude',
        'location_longitude',
        'location_accuracy',
        'ip_address',
        'user_agent',
        'is_valid',
        'validation_notes',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'record_time' => 'datetime',
        'location_latitude' => 'decimal:8',
        'location_longitude' => 'decimal:8',
        'location_accuracy' => 'decimal:2',
        'is_valid' => 'boolean',
        'verified_at' => 'datetime',
    ];

    // =============================================================================
    // Accessors
    // =============================================================================

    public function getSourceNameAttribute(): string
    {
        return match ($this->source) {
            self::SOURCE_BIOMETRIC => 'بصمة',
            self::SOURCE_MANUAL => 'يدوي',
            self::SOURCE_MOBILE => 'جوال',
            self::SOURCE_WEB => 'ويب',
            default => $this->source,
        };
    }

    public function getTypeNameAttribute(): string
    {
        return match ($this->record_type) {
            self::TYPE_CHECK_IN => 'حضور',
            self::TYPE_CHECK_OUT => 'انصراف',
            self::TYPE_BREAK_START => 'بداية استراحة',
            self::TYPE_BREAK_END => 'نهاية استراحة',
            default => $this->record_type,
        };
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function rosterAssignment(): BelongsTo
    {
        return $this->belongsTo(RosterAssignment::class);
    }

    public function biometricDevice(): BelongsTo
    {
        return $this->belongsTo(BiometricDevice::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'verified_by');
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    public function scopeCheckIns($query)
    {
        return $query->where('record_type', self::TYPE_CHECK_IN);
    }

    public function scopeCheckOuts($query)
    {
        return $query->where('record_type', self::TYPE_CHECK_OUT);
    }

    public function scopeValid($query)
    {
        return $query->where('is_valid', true);
    }

    public function scopeFromBiometric($query)
    {
        return $query->where('source', self::SOURCE_BIOMETRIC);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('record_time', $date);
    }

    public function scopeForEmployee($query, string $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    // =============================================================================
    // Methods
    // =============================================================================

    /**
     * التحقق من صحة السجل
     */
    public function verify(string $verifierId, bool $isValid, ?string $notes = null): bool
    {
        $this->is_valid = $isValid;
        $this->verified_by = $verifierId;
        $this->verified_at = now();
        $this->validation_notes = $notes;

        return $this->save();
    }

    /**
     * التحقق من الموقع الجغرافي
     */
    public function isWithinLocation(float $targetLat, float $targetLng, float $radiusMeters): bool
    {
        if (!$this->location_latitude || !$this->location_longitude) {
            return false;
        }

        $distance = $this->calculateDistance(
            $this->location_latitude,
            $this->location_longitude,
            $targetLat,
            $targetLng
        );

        return $distance <= $radiusMeters;
    }

    /**
     * حساب المسافة بين نقطتين
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
