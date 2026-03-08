<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use Auditable, HasFactory, HasUuid, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'employee_number',
        'department_id',
        'position_id',
        'direct_manager_id',
        'hire_date',
        'actual_start_date',
        'termination_date',
        'employment_type',
        'status',
        'first_name',
        'second_name',
        'third_name',
        'last_name',
        'first_name_ar',
        'second_name_ar',
        'third_name_ar',
        'last_name_ar',
        'gender',
        'date_of_birth',
        'place_of_birth',
        'marital_status',
        'dependents_count',
        'national_id',
        'id_type',
        'id_expiry_date',
        'passport_number',
        'passport_expiry_date',
        'nationality',
        'nationality_ar',
        'email',
        'personal_email',
        'phone',
        'phone_secondary',
        'address',
        'city',
        'postal_code',
        'bank_name',
        'bank_account_number',
        'iban',
        'gosi_number',
        'photo',
        'blood_type',
        'medical_conditions',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'actual_start_date' => 'date',
            'termination_date' => 'date',
            'date_of_birth' => 'date',
            'id_expiry_date' => 'date',
            'passport_expiry_date' => 'date',
            'dependents_count' => 'integer',
            'metadata' => 'array',
        ];
    }

    // ─── Relationships ───

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function directManager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'direct_manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'direct_manager_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(EmployeeLoan::class);
    }

    public function custodyItems(): HasMany
    {
        return $this->hasMany(CustodyItem::class);
    }

    public function resignations(): HasMany
    {
        return $this->hasMany(Resignation::class);
    }

    public function generatedLetters(): HasMany
    {
        return $this->hasMany(GeneratedLetter::class);
    }

    // ─── Scopes ───

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // ─── Accessors ───

    public function getFullNameEnAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->second_name,
            $this->third_name,
            $this->last_name,
        ])));
    }

    public function getFullNameArAttribute(): string
    {
        $parts = array_filter([
            $this->first_name_ar,
            $this->second_name_ar,
            $this->third_name_ar,
            $this->last_name_ar,
        ]);

        return $parts ? trim(implode(' ', $parts)) : $this->full_name_en;
    }
}
