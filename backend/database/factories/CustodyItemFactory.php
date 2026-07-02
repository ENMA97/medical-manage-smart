<?php

namespace Database\Factories;

use App\Models\CustodyItem;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CustodyItemFactory extends Factory
{
    protected $model = CustodyItem::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'employee_id' => Employee::factory(),
            'item_name' => fake()->randomElement(['Laptop', 'Mobile Phone', 'ID Card', 'Uniform', 'Keys', 'Parking Card']),
            'item_name_ar' => fake()->randomElement(['حاسب محمول', 'هاتف جوال', 'بطاقة هوية', 'زي موحد', 'مفاتيح', 'بطاقة مواقف']),
            'item_type' => fake()->randomElement(['electronics', 'furniture', 'vehicle', 'equipment', 'other']),
            'serial_number' => strtoupper(fake()->bothify('SN-####-????')),
            'asset_tag' => 'AST-' . fake()->unique()->numberBetween(1000, 9999),
            'description' => fake()->sentence(),
            'value' => fake()->numberBetween(500, 10000),
            'condition_on_delivery' => 'new',
            'delivery_date' => now()->subMonths(fake()->numberBetween(1, 12)),
            'status' => 'delivered',
        ];
    }

    public function returned(): static
    {
        return $this->state(fn () => [
            'status' => 'returned',
            'actual_return_date' => now(),
            'condition_on_return' => 'good',
        ]);
    }
}
