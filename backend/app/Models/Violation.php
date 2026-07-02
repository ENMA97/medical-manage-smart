<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Violation extends Model
{
    use HasFactory, HasUuids, Auditable, SoftDeletes;

    protected $fillable = [
        'violation_number', 'employee_id', 'violation_type_id',
        'violation_date', 'violation_time', 'location',
        'description', 'description_ar', 'occurrence_number',
        'status', 'reported_by', 'evidence', 'witnesses',
        'employee_statement', 'employee_statement_ar',
    ];

    protected $casts = [
        'violation_date' => 'date',
        'evidence' => 'array',
        'witnesses' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function violationType(): BelongsTo
    {
        return $this->belongsTo(ViolationType::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function committee(): HasOne
    {
        return $this->hasOne(InvestigationCommittee::class);
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(DisciplinaryDecision::class);
    }

    public function latestDecision(): HasOne
    {
        return $this->hasOne(DisciplinaryDecision::class)->latestOfMany();
    }
}
