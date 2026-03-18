<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\GeneratedLetter;
use App\Models\LetterTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GeneratedLetterFactory extends Factory
{
    protected $model = GeneratedLetter::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'template_id' => LetterTemplate::factory(),
            'employee_id' => Employee::factory(),
            'letter_number' => 'LTR-' . date('Y') . '-' . str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'letter_type' => fake()->randomElement(['experience_certificate', 'salary_certificate', 'employment_certificate']),
            'content' => 'This is a generated letter content.',
            'content_ar' => 'هذا محتوى خطاب مُولّد.',
            'variables_used' => ['employee_name' => 'Test Employee', 'department' => 'IT'],
            'status' => 'pending',
            'generated_by' => User::factory()->state(['user_type' => 'super_admin']),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }
}
