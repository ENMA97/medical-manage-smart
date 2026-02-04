<?php

namespace App\Models\Leave;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeApprover extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'employee_approvers';

    protected $fillable = [
        'employee_id',
        'approver_id',
        'approver_role',
        'is_primary',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * أدوار المعتمدين
     */
    public const APPROVER_ROLES = [
        'direct_manager' => 'المدير المباشر',
        'admin_manager' => 'المدير الإداري',
        'medical_director' => 'المدير الطبي',
        'department_head' => 'رئيس القسم',
        'backup_approver' => 'المعتمد البديل',
    ];

    /**
     * العلاقة مع الموظف
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * العلاقة مع المعتمد
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * الحصول على اسم دور المعتمد بالعربية
     */
    public function getApproverRoleNameAttribute(): string
    {
        return self::APPROVER_ROLES[$this->approver_role] ?? $this->approver_role;
    }

    /**
     * التحقق من أن المعتمد فعال حالياً
     */
    public function isCurrentlyEffective(): bool
    {
        $now = now()->toDateString();

        if (!$this->is_active) {
            return false;
        }

        if ($this->effective_from && $now < $this->effective_from) {
            return false;
        }

        if ($this->effective_to && $now > $this->effective_to) {
            return false;
        }

        return true;
    }

    /**
     * الحصول على المعتمد للموظف حسب الدور
     */
    public static function getApproverForEmployee(string $employeeId, string $role): ?User
    {
        $approver = self::where('employee_id', $employeeId)
            ->where('approver_role', $role)
            ->where('is_active', true)
            ->where('is_primary', true)
            ->where(function ($query) {
                $query->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', now());
            })
            ->first();

        return $approver?->approver;
    }

    /**
     * الحصول على جميع المعتمدين للموظف
     */
    public static function getApproversForEmployee(string $employeeId): array
    {
        $approvers = [];

        foreach (array_keys(self::APPROVER_ROLES) as $role) {
            $user = self::getApproverForEmployee($employeeId, $role);
            if ($user) {
                $approvers[$role] = $user;
            }
        }

        return $approvers;
    }

    /**
     * Scope للمعتمدين النشطين
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope للمعتمدين الرئيسيين
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope حسب دور المعتمد
     */
    public function scopeByRole($query, string $role)
    {
        return $query->where('approver_role', $role);
    }

    /**
     * Scope للمعتمدين الفعالين حالياً
     */
    public function scopeCurrentlyEffective($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', now());
            });
    }
}
