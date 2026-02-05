<?php

namespace App\Services\HR;

use App\Models\HR\Employee;
use App\Models\HR\Contract;
use App\Services\Leave\LeaveBalanceService;
use Illuminate\Support\Facades\DB;
use Exception;

class EmployeeService
{
    protected LeaveBalanceService $leaveBalanceService;

    public function __construct(LeaveBalanceService $leaveBalanceService)
    {
        $this->leaveBalanceService = $leaveBalanceService;
    }

    /**
     * إنشاء موظف جديد
     */
    public function createEmployee(array $data, string $createdBy): Employee
    {
        return DB::transaction(function () use ($data, $createdBy) {
            // توليد رقم الموظف
            $data['employee_number'] = $this->generateEmployeeNumber();

            // إنشاء الموظف
            $employee = Employee::create($data);

            // إذا كان هناك بيانات عقد، إنشاء العقد
            if (isset($data['contract'])) {
                $this->createContract($employee, $data['contract'], $createdBy);
            }

            // تهيئة أرصدة الإجازات
            if ($employee->contract_type) {
                $this->leaveBalanceService->initializeBalancesForEmployee(
                    $employee->id,
                    $employee->contract_type,
                    $createdBy
                );
            }

            return $employee;
        });
    }

    /**
     * تحديث بيانات الموظف
     */
    public function updateEmployee(Employee $employee, array $data): Employee
    {
        $employee->update($data);

        return $employee->fresh();
    }

    /**
     * إنشاء عقد للموظف
     */
    public function createContract(Employee $employee, array $contractData, string $createdBy): Contract
    {
        // إلغاء العقد الحالي إن وجد
        $employee->contracts()->where('is_active', true)->update(['is_active' => false]);

        // توليد رقم العقد
        $contractData['contract_number'] = $this->generateContractNumber();
        $contractData['employee_id'] = $employee->id;
        $contractData['is_active'] = true;

        // حساب إجمالي الراتب
        $contractData['total_salary'] = ($contractData['basic_salary'] ?? 0) +
                                        ($contractData['housing_allowance'] ?? 0) +
                                        ($contractData['transportation_allowance'] ?? 0) +
                                        ($contractData['other_allowances'] ?? 0);

        $contract = Contract::create($contractData);

        // تحديث نوع العقد في الموظف
        $employee->update(['contract_type' => $contract->contract_type]);

        return $contract;
    }

    /**
     * تجديد عقد الموظف
     */
    public function renewContract(Contract $contract, array $newTerms): Contract
    {
        return DB::transaction(function () use ($contract, $newTerms) {
            $newEndDate = new \DateTime($newTerms['end_date']);
            unset($newTerms['end_date']);

            return $contract->renew($newEndDate, $newTerms);
        });
    }

    /**
     * إنهاء خدمات الموظف
     */
    public function terminateEmployee(Employee $employee, string $reason, string $terminatedBy): Employee
    {
        return DB::transaction(function () use ($employee, $reason, $terminatedBy) {
            // إنهاء العقد الحالي
            if ($currentContract = $employee->currentContract) {
                $currentContract->terminate($reason);
            }

            // تحديث حالة الموظف
            $employee->update([
                'is_active' => false,
                'employment_status' => 'terminated',
            ]);

            return $employee->fresh();
        });
    }

    /**
     * البحث عن الموظفين
     */
    public function searchEmployees(array $filters): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = Employee::with(['department', 'position', 'currentContract']);

        // فلترة حسب الحالة
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // فلترة حسب القسم
        if (isset($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        // فلترة حسب نوع العقد
        if (isset($filters['contract_type'])) {
            $query->where('contract_type', $filters['contract_type']);
        }

        // فلترة حسب الكادر الطبي
        if (isset($filters['is_medical_staff'])) {
            $query->where('is_medical_staff', $filters['is_medical_staff']);
        }

        // البحث النصي
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('employee_number', 'like', "%{$search}%")
                  ->orWhere('first_name_ar', 'like', "%{$search}%")
                  ->orWhere('first_name_en', 'like', "%{$search}%")
                  ->orWhere('last_name_ar', 'like', "%{$search}%")
                  ->orWhere('last_name_en', 'like', "%{$search}%")
                  ->orWhere('national_id', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('employee_number')->paginate($filters['per_page'] ?? 15);
    }

    /**
     * الحصول على إحصائيات الموظفين
     */
    public function getStatistics(): array
    {
        return [
            'total' => Employee::count(),
            'active' => Employee::active()->count(),
            'inactive' => Employee::where('is_active', false)->count(),
            'medical_staff' => Employee::medicalStaff()->count(),
            'on_probation' => Employee::onProbation()->count(),
            'by_contract_type' => Employee::active()
                ->select('contract_type', DB::raw('count(*) as count'))
                ->groupBy('contract_type')
                ->pluck('count', 'contract_type'),
            'by_department' => Employee::active()
                ->select('department_id', DB::raw('count(*) as count'))
                ->groupBy('department_id')
                ->with('department:id,name_ar')
                ->get()
                ->pluck('count', 'department.name_ar'),
        ];
    }

    /**
     * الموظفون الذين ستنتهي عقودهم قريباً
     */
    public function getExpiringContracts(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return Employee::active()
            ->whereHas('currentContract', function ($query) use ($days) {
                $query->where('end_date', '<=', now()->addDays($days))
                      ->where('end_date', '>', now());
            })
            ->with(['currentContract', 'department', 'position'])
            ->get();
    }

    /**
     * توليد رقم موظف جديد
     */
    protected function generateEmployeeNumber(): string
    {
        $year = date('Y');
        $lastEmployee = Employee::where('employee_number', 'like', "EMP{$year}%")
            ->orderBy('employee_number', 'desc')
            ->first();

        if ($lastEmployee) {
            $lastNumber = (int) substr($lastEmployee->employee_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return 'EMP' . $year . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * توليد رقم عقد جديد
     */
    protected function generateContractNumber(): string
    {
        $year = date('Y');
        $lastContract = Contract::where('contract_number', 'like', "CON{$year}%")
            ->orderBy('contract_number', 'desc')
            ->first();

        if ($lastContract) {
            $lastNumber = (int) substr($lastContract->contract_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return 'CON' . $year . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
