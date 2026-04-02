<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class EmployeeGoal extends Model
{
    use Auditable, HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'employee_id',
        'cycle_id',
        'title',
        'title_ar',
        'description',
        'description_ar',
        'type',
        'priority',
        'start_date',
        'target_date',
        'completed_date',
        'progress_percentage',
        'weight',
        'status',
        'milestones',
        'key_results',
        'manager_feedback',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'target_date' => 'date',
            'completed_date' => 'date',
            'progress_percentage' => 'integer',
            'weight' => 'decimal:2',
            'milestones' => 'array',
            'key_results' => 'array',
        ];
    }

    // ─── Relationships ───

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(AppraisalCycle::class, 'cycle_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
