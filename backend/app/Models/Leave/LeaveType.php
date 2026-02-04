<?php

namespace App\Models\Leave;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'leave_types';

    protected $fillable = [
        'code',
        'name',
        'name_ar',
        'description',
        'description_ar',
        'category',
        'default_days_per_year',
        'is_paid',
        'requires_attachment',
        'requires_hr_approval',
        'requires_manager_approval',
        'min_days',
        'max_days',
        'advance_notice_days',
        'can_be_carried_over',
        'max_carry_over_days',
        'applicable_contract_types',
        'gender_restriction',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'default_days_per_year' => 'integer',
        'is_paid' => 'boolean',
        'requires_attachment' => 'boolean',
        'requires_hr_approval' => 'boolean',
        'requires_manager_approval' => 'boolean',
        'min_days' => 'integer',
        'max_days' => 'integer',
        'advance_notice_days' => 'integer',
        'can_be_carried_over' => 'boolean',
        'max_carry_over_days' => 'integer',
        'applicable_contract_types' => 'array',
        'gender_restriction' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * أنواع/تصنيفات الإجازات
     */
    public const CATEGORIES = [
        'annual' => 'سنوية',
        'sick' => 'مرضية',
        'emergency' => 'طارئة',
        'unpaid' => 'بدون راتب',
        'maternity' => 'أمومة',
        'paternity' => 'أبوة',
        'hajj' => 'حج',
        'marriage' => 'زواج',
        'bereavement' => 'وفاة',
        'study' => 'دراسية',
        'compensatory' => 'تعويضية',
        'other' => 'أخرى',
    ];

    /**
     * العلاقة مع أرصدة الإجازات
     */
    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    /**
     * العلاقة مع طلبات الإجازة
     */
    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * العلاقة مع سياسات الإجازات
     */
    public function policies(): HasMany
    {
        return $this->hasMany(LeavePolicy::class);
    }

    /**
     * Scope للإجازات النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope للإجازات المدفوعة
     */
    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    /**
     * Scope حسب التصنيف
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * التحقق من إمكانية التقديم على هذا النوع
     */
    public function canApply(string $contractType, ?string $gender = null): bool
    {
        // التحقق من نوع العقد
        if ($this->applicable_contract_types) {
            if (!in_array($contractType, $this->applicable_contract_types)) {
                return false;
            }
        }

        // التحقق من الجنس
        if ($this->gender_restriction && $gender) {
            if (!in_array($gender, $this->gender_restriction)) {
                return false;
            }
        }

        return $this->is_active;
    }

    /**
     * الحصول على اسم التصنيف بالعربية
     */
    public function getCategoryNameAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }
}
