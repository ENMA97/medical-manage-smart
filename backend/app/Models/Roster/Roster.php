<?php

namespace App\Models\Roster;

use App\Models\HR\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Roster extends Model
{
    use HasFactory, HasUuids;

    /**
     * حالات الجدول
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_LOCKED = 'locked';

    public const STATUSES = [
        self::STATUS_DRAFT => 'مسودة',
        self::STATUS_PUBLISHED => 'منشور',
        self::STATUS_LOCKED => 'مغلق',
    ];

    protected $fillable = [
        'roster_number',
        'department_id',
        'year',
        'month',
        'status',
        'created_by',
        'published_by',
        'published_at',
        'locked_by',
        'locked_at',
        'notes',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'published_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    // =============================================================================
    // Boot Methods
    // =============================================================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($roster) {
            if (empty($roster->roster_number)) {
                $roster->roster_number = self::generateRosterNumber($roster->department_id, $roster->year, $roster->month);
            }
        });
    }

    // =============================================================================
    // Accessors
    // =============================================================================

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getIsLockedAttribute(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }

    public function getIsPublishedAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_PUBLISHED, self::STATUS_LOCKED]);
    }

    public function getPeriodNameAttribute(): string
    {
        $months = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];

        return ($months[$this->month] ?? $this->month) . ' ' . $this->year;
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(RosterAssignment::class);
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    public function scopeForPeriod($query, int $year, int $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    public function scopePublished($query)
    {
        return $query->whereIn('status', [self::STATUS_PUBLISHED, self::STATUS_LOCKED]);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    // =============================================================================
    // Static Methods
    // =============================================================================

    public static function generateRosterNumber(string $departmentId, int $year, int $month): string
    {
        $prefix = 'RST';
        $yearMonth = $year . str_pad($month, 2, '0', STR_PAD_LEFT);
        $deptShort = substr($departmentId, 0, 4);

        return "{$prefix}-{$yearMonth}-{$deptShort}";
    }
}
