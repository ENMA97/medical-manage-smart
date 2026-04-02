<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class EmployeeCertificate extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'employee_id',
        'name',
        'name_ar',
        'type',
        'issuing_body',
        'issuing_body_ar',
        'certificate_number',
        'issue_date',
        'expiry_date',
        'is_verified',
        'verified_by',
        'verified_at',
        'document_path',
        'renewal_required',
        'renewal_reminder_days',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
            'renewal_required' => 'boolean',
            'renewal_reminder_days' => 'integer',
        ];
    }

    // ─── Relationships ───

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
