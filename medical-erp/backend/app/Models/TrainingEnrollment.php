<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class TrainingEnrollment extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'course_id',
        'employee_id',
        'status',
        'nominated_by',
        'approved_by',
        'approved_at',
        'score',
        'attendance_percentage',
        'feedback',
        'rating',
        'manager_feedback',
        'certificate_date',
        'certificate_number',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'score' => 'decimal:2',
            'attendance_percentage' => 'decimal:2',
            'rating' => 'integer',
            'certificate_date' => 'date',
        ];
    }

    // ─── Relationships ───

    public function course(): BelongsTo
    {
        return $this->belongsTo(TrainingCourse::class, 'course_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function nominatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nominated_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
