<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class TrainingCourse extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'program_id',
        'title',
        'title_ar',
        'description',
        'description_ar',
        'objectives',
        'objectives_ar',
        'delivery_method',
        'provider',
        'provider_ar',
        'trainer_name',
        'location',
        'location_ar',
        'start_date',
        'end_date',
        'duration_hours',
        'max_participants',
        'cost_per_person',
        'total_cost',
        'status',
        'certificate_issued',
        'is_mandatory',
        'prerequisites',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'duration_hours' => 'integer',
            'max_participants' => 'integer',
            'cost_per_person' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'certificate_issued' => 'boolean',
            'is_mandatory' => 'boolean',
            'prerequisites' => 'array',
        ];
    }

    // ─── Relationships ───

    public function program(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class, 'program_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(TrainingEnrollment::class, 'course_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
