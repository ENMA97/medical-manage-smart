<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'first_name',
        'last_name',
        'first_name_ar',
        'last_name_ar',
        'email',
        'phone',
        'phone_secondary',
        'gender',
        'date_of_birth',
        'nationality',
        'nationality_ar',
        'national_id',
        'city',
        'address',
        'current_employer',
        'current_position',
        'years_of_experience',
        'expected_salary',
        'current_salary',
        'highest_education',
        'university',
        'major',
        'resume_path',
        'skills',
        'languages',
        'certifications',
        'source',
        'referred_by_employee_id',
        'notes',
        'is_blacklisted',
        'blacklist_reason',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'years_of_experience' => 'integer',
            'expected_salary' => 'decimal:2',
            'current_salary' => 'decimal:2',
            'skills' => 'array',
            'languages' => 'array',
            'certifications' => 'array',
            'is_blacklisted' => 'boolean',
        ];
    }

    protected $appends = ['full_name'];

    // ─── Relationships ───

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function referredByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'referred_by_employee_id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(JobOffer::class);
    }

    // ─── Accessors ───

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
