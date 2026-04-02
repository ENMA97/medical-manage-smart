<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class JobOffer extends Model
{
    use Auditable, HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'offer_number',
        'application_id',
        'candidate_id',
        'position_id',
        'department_id',
        'offered_salary',
        'allowances',
        'benefits',
        'contract_type',
        'contract_duration_months',
        'probation_months',
        'proposed_start_date',
        'offer_valid_until',
        'additional_terms',
        'additional_terms_ar',
        'status',
        'approved_by',
        'approved_at',
        'sent_at',
        'responded_at',
        'decline_reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'offered_salary' => 'decimal:2',
            'allowances' => 'array',
            'benefits' => 'array',
            'contract_duration_months' => 'integer',
            'probation_months' => 'integer',
            'proposed_start_date' => 'date',
            'offer_valid_until' => 'date',
            'approved_at' => 'datetime',
            'sent_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    // ─── Relationships ───

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'application_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
