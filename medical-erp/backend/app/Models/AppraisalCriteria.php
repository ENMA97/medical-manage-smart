<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class AppraisalCriteria extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;
    protected $table = 'appraisal_criteria';

    protected $fillable = [
        'template_id',
        'name',
        'name_ar',
        'description',
        'description_ar',
        'category',
        'weight',
        'max_score',
        'sort_order',
        'is_required',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'max_score' => 'decimal:2',
            'sort_order' => 'integer',
            'is_required' => 'boolean',
        ];
    }

    // ─── Relationships ───

    public function template(): BelongsTo
    {
        return $this->belongsTo(AppraisalTemplate::class, 'template_id');
    }
}
