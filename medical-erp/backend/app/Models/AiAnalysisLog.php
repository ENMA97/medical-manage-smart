<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiAnalysisLog extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'analysis_type', 'model_version', 'input_parameters', 'results',
        'confidence_score', 'data_points_analyzed', 'time_range_start',
        'time_range_end', 'processing_time_ms', 'status', 'error_message',
        'triggered_by', 'created_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'input_parameters' => 'array',
            'results' => 'array',
            'confidence_score' => 'decimal:4',
            'created_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(AiPrediction::class, 'analysis_log_id');
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(AiRecommendation::class, 'analysis_log_id');
    }
}
