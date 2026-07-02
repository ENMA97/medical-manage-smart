<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'code',
        'name',
        'name_ar',
        'category',
        'default_days_per_year',
        'max_days_per_request',
        'min_days_per_request',
        'is_paid',
        'pay_percentage',
        'requires_attachment',
        'requires_substitute',
        'advance_notice_days',
        'carries_forward',
        'max_carry_forward_days',
        'is_active',
        'description',
        'policy_notes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'default_days_per_year' => 'integer',
            'max_days_per_request' => 'integer',
            'min_days_per_request' => 'integer',
            'advance_notice_days' => 'integer',
            'max_carry_forward_days' => 'integer',
            'sort_order' => 'integer',
            'is_paid' => 'boolean',
            'requires_attachment' => 'boolean',
            'requires_substitute' => 'boolean',
            'carries_forward' => 'boolean',
            'is_active' => 'boolean',
            'pay_percentage' => 'decimal:2',
        ];
    }

    // ─── Relationships ───

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
