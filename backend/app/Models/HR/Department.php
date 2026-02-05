<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'description_ar',
        'description_en',
        'parent_id',
        'manager_id',
        'cost_center_id',
        'is_medical',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_medical' => 'boolean',
        'is_active' => 'boolean',
    ];

    // =============================================================================
    // Accessors
    // =============================================================================

    /**
     * اسم القسم حسب اللغة الحالية
     */
    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->name_ar : $this->name_en;
    }

    /**
     * الوصف حسب اللغة الحالية
     */
    public function getDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->description_ar : $this->description_en;
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    /**
     * القسم الأب
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    /**
     * الأقسام الفرعية
     */
    public function children(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    /**
     * مدير القسم
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * موظفو القسم
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

    /**
     * المناصب في القسم
     */
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    /**
     * الأقسام النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * الأقسام الطبية
     */
    public function scopeMedical($query)
    {
        return $query->where('is_medical', true);
    }

    /**
     * الأقسام الرئيسية (بدون أب)
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    // =============================================================================
    // Methods
    // =============================================================================

    /**
     * عدد الموظفين النشطين
     */
    public function getActiveEmployeesCount(): int
    {
        return $this->activeEmployees()->count();
    }

    /**
     * الحصول على جميع الأقسام الفرعية بشكل متداخل
     */
    public function getAllDescendants(): \Illuminate\Support\Collection
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getAllDescendants());
        }

        return $descendants;
    }

    /**
     * المسار الكامل للقسم
     */
    public function getFullPath(string $separator = ' > '): string
    {
        $path = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode($separator, $path);
    }
}
