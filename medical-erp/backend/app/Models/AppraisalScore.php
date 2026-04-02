<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class AppraisalScore extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'appraisal_id',
        'criteria_id',
        'self_score',
        'manager_score',
        'final_score',
        'self_comment',
        'manager_comment',
    ];

    protected function casts(): array
    {
        return [
            'self_score' => 'decimal:2',
            'manager_score' => 'decimal:2',
            'final_score' => 'decimal:2',
        ];
    }

    // ─── Relationships ───

    public function appraisal(): BelongsTo
    {
        return $this->belongsTo(EmployeeAppraisal::class, 'appraisal_id');
    }

    public function criteria(): BelongsTo
    {
        return $this->belongsTo(AppraisalCriteria::class, 'criteria_id');
    }
}
