<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\ContractAlert;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ContractAlertFactory extends Factory
{
    protected $model = ContractAlert::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'contract_id' => Contract::factory(),
            'employee_id' => Employee::factory(),
            'alert_type' => fake()->randomElement(['expiry_warning', 'expiry_urgent', 'expired']),
            'days_before_expiry' => fake()->randomElement([30, 15, 7]),
            'alert_date' => now()->toDateString(),
            'expiry_date' => now()->addDays(30)->toDateString(),
            'status' => 'pending',
            'message_ar' => 'تنبيه: عقد الموظف قارب على الانتهاء',
        ];
    }
}
