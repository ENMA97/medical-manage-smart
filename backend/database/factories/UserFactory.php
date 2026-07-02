<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'username' => $this->faker->unique()->userName(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => '05' . $this->faker->numerify('########'),
            'full_name' => $this->faker->name(),
            'full_name_ar' => 'مستخدم تجريبي',
            'user_type' => 'employee',
            'preferred_language' => 'ar',
            'is_active' => true,
            'receive_notifications' => true,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['user_type' => 'super_admin']);
    }

    public function hrManager(): static
    {
        return $this->state(fn () => ['user_type' => 'hr_manager']);
    }
}
