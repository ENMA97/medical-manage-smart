<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ContractRenewal extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'contract_id',
        'employee_id',
        'employee_response',
        'employee_response_at',
        'employee_remarks',
        'management_decision',
        'decided_by',
        'decided_at',
        'management_remarks',
        'new_terms',
        'new_contract_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'employee_response_at' => 'datetime',
            'decided_at' => 'datetime',
            'new_terms' => 'array',
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

    public function newContract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'new_contract_id');
    }
}
