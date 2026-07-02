<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestigationSession extends Model
{
    use HasUuids, Auditable;

    protected $fillable = [
        'committee_id', 'session_number', 'session_date', 'location',
        'agenda', 'agenda_ar', 'minutes', 'minutes_ar',
        'employee_response', 'employee_response_ar',
        'employee_attended', 'employee_absence_reason',
        'attendees', 'attachments', 'status',
    ];

    protected $casts = [
        'session_date' => 'datetime',
        'employee_attended' => 'boolean',
        'attendees' => 'array',
        'attachments' => 'array',
    ];

    public function committee(): BelongsTo
    {
        return $this->belongsTo(InvestigationCommittee::class, 'committee_id');
    }
}
