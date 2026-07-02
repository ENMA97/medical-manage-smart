<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiPrediction extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'analysis_log_id', 'prediction_type', 'department_id',
        'prediction_date', 'prediction_end_date', 'description', 'description_ar',
        'probability', 'impact_level', 'affected_positions', 'affected_count',
        'suggested_actions', 'is_acknowledged', 'acknowledged_by', 'acknowledged_at',
        'was_accurate',
    ];

    protected function casts(): array
    {
        return [
            'prediction_date' => 'date',
            'prediction_end_date' => 'date',
            'probability' => 'decimal:4',
            'affected_positions' => 'array',
            'suggested_actions' => 'array',
            'is_acknowledged' => 'boolean',
            'acknowledged_at' => 'datetime',
            'was_accurate' => 'boolean',
        ];
    }

    public function analysisLog(): BelongsTo
    {
        return $this->belongsTo(AiAnalysisLog::class, 'analysis_log_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }
}
