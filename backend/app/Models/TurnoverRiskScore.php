<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TurnoverRiskScore extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'employee_id', 'analysis_log_id', 'risk_score', 'risk_level',
        'risk_factors', 'recommended_actions', 'assessment_date', 'valid_until',
        'is_latest', 'action_taken', 'action_by', 'action_notes',
    ];

    protected function casts(): array
    {
        return [
            'risk_score' => 'decimal:4',
            'risk_factors' => 'array',
            'recommended_actions' => 'array',
            'assessment_date' => 'date',
            'valid_until' => 'date',
            'is_latest' => 'boolean',
            'action_taken' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function analysisLog(): BelongsTo
    {
        return $this->belongsTo(AiAnalysisLog::class, 'analysis_log_id');
    }

    public function actionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_by');
    }
}
