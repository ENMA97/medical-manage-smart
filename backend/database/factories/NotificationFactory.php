<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'user_id' => User::factory(),
            'channel' => fake()->randomElement(['in_app', 'email', 'sms', 'push']),
            'type' => fake()->randomElement(['leave_request', 'contract_expiry', 'payroll', 'general']),
            'title' => fake()->sentence(4),
            'title_ar' => 'إشعار جديد',
            'body' => fake()->paragraph(1),
            'body_ar' => 'محتوى الإشعار',
            'is_sent' => true,
            'sent_at' => now(),
        ];
    }

    public function unread(): static
    {
        return $this->state(fn () => ['read_at' => null]);
    }

    public function read(): static
    {
        return $this->state(fn () => ['read_at' => now()]);
    }
}
