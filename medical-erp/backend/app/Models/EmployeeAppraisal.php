<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class EmployeeAppraisal extends Model
{
    use Auditable, HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'cycle_id',
        'employee_id',
        'template_id',
        'reviewer_id',
        'status',
        'self_score',
        'manager_score',
        'final_score',
        'final_rating',
        'self_comments',
        'manager_comments',
        'hr_comments',
        'improvement_plan',
        'strengths',
        'weaknesses',
        'self_review_date',
        'manager_review_date',
        'completed_at',
        'acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'self_score' => 'decimal:2',
            'manager_score' => 'decimal:2',
            'final_score' => 'decimal:2',
            'self_review_date' => 'datetime',
            'manager_review_date' => 'datetime',
            'completed_at' => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    // ─── Relationships ───

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(AppraisalCycle::class, 'cycle_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AppraisalTemplate::class, 'template_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(AppraisalScore::class, 'appraisal_id');
    }
}
