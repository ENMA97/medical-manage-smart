<?php

namespace Database\Factories;

use App\Models\LetterTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LetterTemplateFactory extends Factory
{
    protected $model = LetterTemplate::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'code' => 'TPL-' . strtoupper(fake()->unique()->bothify('???##')),
            'name' => fake()->randomElement(['Experience Certificate', 'Salary Certificate', 'Employment Certificate']),
            'name_ar' => 'شهادة',
            'letter_type' => fake()->randomElement(['experience_certificate', 'salary_certificate', 'employment_certificate']),
            'body_template' => 'This is to certify that {{employee_name}} (Employee #{{employee_number}}) works at our organization in the {{department}} department as {{position}}.',
            'body_template_ar' => 'نشهد بأن {{employee_name}} (رقم الموظف #{{employee_number}}) يعمل في مؤسستنا في قسم {{department}} بمنصب {{position}}.',
            'available_variables' => ['employee_name', 'employee_number', 'department', 'position', 'hire_date', 'national_id', 'date'],
            'default_settings' => ['font_size' => 14, 'margin' => 20],
            'requires_approval' => true,
            'is_active' => true,
        ];
    }

    public function noApproval(): static
    {
        return $this->state(fn () => ['requires_approval' => false]);
    }
}
