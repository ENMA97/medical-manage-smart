<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'application_id',
        'round',
        'type',
        'scheduled_at',
        'duration_minutes',
        'location',
        'interviewers',
        'primary_interviewer_id',
        'status',
        'overall_score',
        'recommendation',
        'strengths',
        'concerns',
        'feedback',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'round' => 'integer',
            'scheduled_at' => 'datetime',
            'duration_minutes' => 'integer',
            'interviewers' => 'array',
            'overall_score' => 'decimal:2',
        ];
    }

    // ─── Relationships ───

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'application_id');
    }

    public function primaryInterviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'primary_interviewer_id');
    }
}
