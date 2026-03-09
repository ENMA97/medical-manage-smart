<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvestigationCommittee extends Model
{
    use HasFactory, HasUuids, Auditable;

    protected $fillable = [
        'committee_number', 'name', 'name_ar',
        'violation_id', 'chairman_id',
        'formation_date', 'deadline', 'status',
        'mandate', 'mandate_ar', 'formed_by',
    ];

    protected $casts = [
        'formation_date' => 'date',
        'deadline' => 'date',
    ];

    public function violation(): BelongsTo
    {
        return $this->belongsTo(Violation::class);
    }

    public function chairman(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'chairman_id');
    }

    public function formedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'formed_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(CommitteeMember::class, 'committee_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(InvestigationSession::class, 'committee_id');
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(DisciplinaryDecision::class, 'committee_id');
    }
}
