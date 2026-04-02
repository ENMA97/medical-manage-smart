<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class JobRequisition extends Model
{
    use Auditable, HasFactory, HasUuid, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'requisition_number',
        'department_id',
        'position_id',
        'requested_by',
        'vacancies',
        'employment_type',
        'priority',
        'justification',
        'justification_ar',
        'job_description',
        'job_description_ar',
        'requirements',
        'requirements_ar',
        'qualifications',
        'min_experience_years',
        'salary_range_min',
        'salary_range_max',
        'target_hire_date',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'vacancies' => 'integer',
            'qualifications' => 'array',
            'min_experience_years' => 'integer',
            'salary_range_min' => 'decimal:2',
            'salary_range_max' => 'decimal:2',
            'target_hire_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    // ─── Relationships ───

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function postings(): HasMany
    {
        return $this->hasMany(JobPosting::class, 'requisition_id');
    }
}
