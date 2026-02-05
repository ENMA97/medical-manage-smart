<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'code',
        'title_ar',
        'title_en',
        'description_ar',
        'description_en',
        'department_id',
        'level',
        'min_salary',
        'max_salary',
        'is_medical',
        'requires_license',
        'is_managerial',
        'headcount',
        'is_active',
        'qualifications',
        'responsibilities',
        'sort_order',
    ];

    protected $casts = [
        'min_salary' => 'decimal:2',
        'max_salary' => 'decimal:2',
        'is_medical' => 'boolean',
        'requires_license' => 'boolean',
        'is_managerial' => 'boolean',
        'is_active' => 'boolean',
        'qualifications' => 'array',
        'responsibilities' => 'array',
    ];

    // =============================================================================
    // Accessors
    // =============================================================================

    /**
     * المسمى الوظيفي حسب اللغة الحالية
     */
    public function getTitleAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->title_ar : $this->title_en;
    }

    /**
     * الوصف حسب اللغة الحالية
     */
    public function getDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->description_ar : $this->description_en;
    }

    /**
     * نطاق الراتب
     */
    public function getSalaryRangeAttribute(): string
    {
        return number_format($this->min_salary) . ' - ' . number_format($this->max_salary);
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    /**
     * القسم
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * الموظفون في هذا المنصب
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * الموظفون النشطون
     */
    public function activeEmployees(): HasMany
    {
        return $this->hasMany(Employee::class)->where('is_active', true);
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    /**
     * المناصب النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * المناصب الطبية
     */
    public function scopeMedical($query)
    {
        return $query->where('is_medical', true);
    }

    /**
     * المناصب الإدارية
     */
    public function scopeManagerial($query)
    {
        return $query->where('is_managerial', true);
    }

    /**
     * المناصب الشاغرة
     */
    public function scopeWithVacancies($query)
    {
        return $query->whereRaw('headcount > (SELECT COUNT(*) FROM employees WHERE position_id = positions.id AND is_active = true)');
    }

    // =============================================================================
    // Methods
    // =============================================================================

    /**
     * عدد الموظفين الحاليين
     */
    public function getCurrentEmployeesCount(): int
    {
        return $this->activeEmployees()->count();
    }

    /**
     * عدد الشواغر
     */
    public function getVacanciesCount(): int
    {
        return max(0, $this->headcount - $this->getCurrentEmployeesCount());
    }

    /**
     * هل المنصب شاغر
     */
    public function hasVacancy(): bool
    {
        return $this->getVacanciesCount() > 0;
    }
}
