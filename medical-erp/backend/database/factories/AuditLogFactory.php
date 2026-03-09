<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'user_id' => User::factory(),
            'user_name' => fake()->name(),
            'event' => fake()->randomElement(['created', 'updated', 'deleted']),
            'auditable_type' => 'App\\Models\\Employee',
            'auditable_id' => Str::uuid()->toString(),
            'description' => 'Test audit log entry',
        ];
    }
}
