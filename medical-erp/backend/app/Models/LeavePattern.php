<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeavePattern extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'pattern_type', 'department_id', 'employee_id',
        'pattern_description', 'pattern_description_ar',
        'pattern_data', 'confidence', 'occurrences',
        'affected_periods', 'impact_score', 'is_active',
        'first_detected_at', 'last_detected_at',
    ];

    protected function casts(): array
    {
        return [
            'pattern_data' => 'array',
            'confidence' => 'decimal:4',
            'affected_periods' => 'array',
            'impact_score' => 'decimal:2',
            'is_active' => 'boolean',
            'first_detected_at' => 'datetime',
            'last_detected_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
