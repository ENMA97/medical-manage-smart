<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PayrollItemFactory extends Factory
{
    protected $model = PayrollItem::class;

    public function definition(): array
    {
        $basicSalary = fake()->numberBetween(5000, 15000);
        $housing = round($basicSalary * 0.25, 2);
        $transport = 500;
        $gosiEmployee = round($basicSalary * 0.0975, 2);

        return [
            'id' => Str::uuid()->toString(),
            'payroll_id' => Payroll::factory(),
            'employee_id' => Employee::factory(),
            'basic_salary' => $basicSalary,
            'housing_allowance' => $housing,
            'transport_allowance' => $transport,
            'food_allowance' => 300,
            'phone_allowance' => 0,
            'other_allowances' => 0,
            'gosi_employee' => $gosiEmployee,
            'gosi_employer' => round($basicSalary * 0.1175, 2),
            'gross_salary' => $basicSalary + $housing + $transport + 300,
            'total_deductions' => $gosiEmployee,
            'net_salary' => $basicSalary + $housing + $transport + 300 - $gosiEmployee,
        ];
    }
}
