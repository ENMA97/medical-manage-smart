<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\ContractRenewal;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ContractRenewalFactory extends Factory
{
    protected $model = ContractRenewal::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'contract_id' => Contract::factory(),
            'employee_id' => Employee::factory(),
            'status' => 'pending',
        ];
    }
}
