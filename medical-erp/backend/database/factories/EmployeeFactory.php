<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'employee_number' => $this->faker->unique()->numerify('EMP-####'),
            'department_id' => Department::factory(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'first_name_ar' => 'محمد',
            'last_name_ar' => 'أحمد',
            'gender' => $this->faker->randomElement(['male', 'female']),
            'date_of_birth' => $this->faker->date('Y-m-d', '-25 years'),
            'national_id' => $this->faker->numerify('##########'),
            'nationality' => 'SA',
            'phone' => '05' . $this->faker->numerify('########'),
            'email' => $this->faker->unique()->safeEmail(),
            'hire_date' => $this->faker->date('Y-m-d', '-1 year'),
            'status' => 'active',
            'employment_type' => 'full_time',
            'marital_status' => 'single',
            'bank_name' => 'الراجحي',
            'iban' => 'SA' . $this->faker->numerify('######################'),
        ];
    }
}
