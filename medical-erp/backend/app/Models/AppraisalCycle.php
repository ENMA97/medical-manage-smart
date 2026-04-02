<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class AppraisalCycle extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'name_ar',
        'type',
        'start_date',
        'end_date',
        'review_deadline',
        'status',
        'description',
        'description_ar',
        'self_evaluation_enabled',
        'peer_evaluation_enabled',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'review_deadline' => 'date',
            'self_evaluation_enabled' => 'boolean',
            'peer_evaluation_enabled' => 'boolean',
        ];
    }

    // ─── Relationships ───

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function appraisals(): HasMany
    {
        return $this->hasMany(EmployeeAppraisal::class, 'cycle_id');
    }

    public function goals(): HasMany
    {
        return $this->hasMany(EmployeeGoal::class, 'cycle_id');
    }

    // ─── Scopes ───

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
