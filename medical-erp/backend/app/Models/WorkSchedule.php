<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class WorkSchedule extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'name_ar',
        'type',
        'rotation_days',
        'weekly_pattern',
        'is_default',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'weekly_pattern' => 'array',
            'rotation_days' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // ─── Relationships ───

    public function employeeSchedules(): HasMany
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    // ─── Scopes ───

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
