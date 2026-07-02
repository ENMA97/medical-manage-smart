<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommitteeMember extends Model
{
    use HasUuids;

    protected $fillable = [
        'committee_id', 'employee_id', 'role', 'role_ar',
    ];

    public function committee(): BelongsTo
    {
        return $this->belongsTo(InvestigationCommittee::class, 'committee_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
