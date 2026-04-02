<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    use Auditable, HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'application_number',
        'posting_id',
        'candidate_id',
        'status',
        'cover_letter',
        'screening_score',
        'screened_by',
        'screened_at',
        'screening_notes',
        'rejection_reason',
        'rejected_by',
        'rejected_at',
        'evaluation_scores',
        'overall_score',
    ];

    protected function casts(): array
    {
        return [
            'screening_score' => 'decimal:2',
            'screened_at' => 'datetime',
            'rejected_at' => 'datetime',
            'evaluation_scores' => 'array',
            'overall_score' => 'decimal:2',
        ];
    }

    // ─── Relationships ───

    public function posting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'posting_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class, 'application_id');
    }

    public function offer(): HasOne
    {
        return $this->hasOne(JobOffer::class, 'application_id');
    }

    public function screenedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'screened_by');
    }
}
