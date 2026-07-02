<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ContractAlert extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'contract_id',
        'employee_id',
        'alert_type',
        'days_before_expiry',
        'alert_date',
        'expiry_date',
        'status',
        'sent_to_employee',
        'sent_to_manager',
        'sent_to_hr',
        'message',
        'message_ar',
        'actioned_by',
        'actioned_at',
    ];

    protected function casts(): array
    {
        return [
            'alert_date' => 'date',
            'expiry_date' => 'date',
            'actioned_at' => 'datetime',
            'sent_to_employee' => 'boolean',
            'sent_to_manager' => 'boolean',
            'sent_to_hr' => 'boolean',
            'days_before_expiry' => 'integer',
        ];
    }

    // ─── Relationships ───

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
