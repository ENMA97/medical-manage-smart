<?php

namespace App\Models\Leave;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicHoliday extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'public_holidays';

    protected $fillable = [
        'name',
        'name_ar',
        'date',
        'year',
        'is_recurring',
        'calendar_type',
        'hijri_month',
        'hijri_day',
        'is_active',
    ];

    protected $casts = [
        'date' => 'date',
        'year' => 'integer',
        'is_recurring' => 'boolean',
        'hijri_month' => 'integer',
        'hijri_day' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * أنواع التقويم
     */
    public const CALENDAR_TYPES = [
        'gregorian' => 'ميلادي',
        'hijri' => 'هجري',
    ];

    /**
     * Scope للعطل النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope للسنة المحددة
     */
    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope للسنة الحالية
     */
    public function scopeCurrentYear($query)
    {
        return $query->where('year', date('Y'));
    }

    /**
     * التحقق من أن اليوم عطلة
     */
    public static function isHoliday($date): bool
    {
        return self::active()
            ->where('date', $date)
            ->exists();
    }

    /**
     * الحصول على العطل في فترة معينة
     */
    public static function getHolidaysInRange($startDate, $endDate)
    {
        return self::active()
            ->whereBetween('date', [$startDate, $endDate])
            ->get();
    }

    /**
     * حساب عدد أيام العطل في فترة
     */
    public static function countHolidaysInRange($startDate, $endDate): int
    {
        return self::active()
            ->whereBetween('date', [$startDate, $endDate])
            ->count();
    }
}
