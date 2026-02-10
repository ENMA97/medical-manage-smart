<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InsuranceClaim extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * حالات المطالبة
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_SCRUBBED = 'scrubbed';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PARTIALLY_APPROVED = 'partially_approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_PAID = 'paid';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';

    public const STATUSES = [
        self::STATUS_DRAFT => 'مسودة',
        self::STATUS_SUBMITTED => 'مرسلة',
        self::STATUS_SCRUBBED => 'تم التدقيق',
        self::STATUS_APPROVED => 'موافق عليها',
        self::STATUS_PARTIALLY_APPROVED => 'موافق عليها جزئياً',
        self::STATUS_REJECTED => 'مرفوضة',
        self::STATUS_PAID => 'مدفوعة',
        self::STATUS_PARTIALLY_PAID => 'مدفوعة جزئياً',
    ];

    protected $fillable = [
        'claim_number',
        'insurance_company_id',
        'patient_name',
        'patient_id_number',
        'policy_number',
        'member_id',
        'service_date',
        'service_id',
        'doctor_id',
        'diagnosis_code',
        'procedure_code',
        'claimed_amount',
        'approved_amount',
        'paid_amount',
        'deduction_amount',
        'deduction_reason',
        'status',
        'scrub_result',
        'scrub_notes',
        'submission_date',
        'approval_date',
        'payment_date',
        'rejection_reason',
        'submitted_by',
        'approved_by',
        'notes',
    ];

    protected $casts = [
        'service_date' => 'date',
        'submission_date' => 'date',
        'approval_date' => 'date',
        'payment_date' => 'date',
        'claimed_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
        'scrub_result' => 'array',
    ];

    // =============================================================================
    // Boot
    // =============================================================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($claim) {
            if (empty($claim->claim_number)) {
                $claim->claim_number = self::generateClaimNumber();
            }
        });
    }

    // =============================================================================
    // Accessors
    // =============================================================================

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getApprovalRateAttribute(): float
    {
        if ($this->claimed_amount <= 0) {
            return 0;
        }
        return round(($this->approved_amount ?? 0) / $this->claimed_amount * 100, 2);
    }

    public function getOutstandingAmountAttribute(): float
    {
        return ($this->approved_amount ?? 0) - ($this->paid_amount ?? 0);
    }

    public function getAgeDaysAttribute(): int
    {
        $startDate = $this->submission_date ?? $this->service_date ?? $this->created_at;
        return $startDate->diffInDays(now());
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(MedicalService::class, 'service_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    public function scopePending($query)
    {
        return $query->whereIn('status', [
            self::STATUS_DRAFT,
            self::STATUS_SUBMITTED,
            self::STATUS_SCRUBBED,
        ]);
    }

    public function scopeApproved($query)
    {
        return $query->whereIn('status', [
            self::STATUS_APPROVED,
            self::STATUS_PARTIALLY_APPROVED,
        ]);
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', [
            self::STATUS_APPROVED,
            self::STATUS_PARTIALLY_APPROVED,
            self::STATUS_PARTIALLY_PAID,
        ]);
    }

    public function scopeAged($query, int $days)
    {
        return $query->where('submission_date', '<=', now()->subDays($days));
    }

    // =============================================================================
    // Static Methods
    // =============================================================================

    public static function generateClaimNumber(): string
    {
        $prefix = 'CLM';
        $date = now()->format('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;

        return "{$prefix}-{$date}-" . str_pad($count, 5, '0', STR_PAD_LEFT);
    }
}
