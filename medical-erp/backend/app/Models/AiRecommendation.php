<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRecommendation extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'analysis_log_id', 'recommendation_type', 'title', 'title_ar',
        'description', 'description_ar', 'priority', 'supporting_data',
        'action_steps', 'estimated_impact', 'impact_unit', 'status',
        'reviewed_by', 'reviewed_at', 'review_notes', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'supporting_data' => 'array',
            'action_steps' => 'array',
            'estimated_impact' => 'decimal:2',
            'reviewed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function analysisLog(): BelongsTo
    {
        return $this->belongsTo(AiAnalysisLog::class, 'analysis_log_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
