<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class EmployeeSkill extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'employee_id',
        'skill_category_id',
        'skill_name',
        'skill_name_ar',
        'proficiency_level',
        'years_of_experience',
        'last_assessed_date',
        'assessed_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'years_of_experience' => 'integer',
            'last_assessed_date' => 'date',
        ];
    }

    // ─── Relationships ───

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function skillCategory(): BelongsTo
    {
        return $this->belongsTo(SkillCategory::class);
    }

    public function assessedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }
}
