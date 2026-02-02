<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'employee_number',
        'name',
        'name_ar',
        'email',
        'phone',
        'national_id',
        'birth_date',
        'gender',
        'nationality',
        'department_id',
        'position_id',
        'hire_date',
        'termination_date',
        'status',
        'bank_name',
        'iban',
        'address',
        'county_id',
        'emergency_contact',
        'emergency_phone',
        'metadata',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'hire_date' => 'date',
        'termination_date' => 'date',
        'metadata' => 'array',
    ];

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }
}
