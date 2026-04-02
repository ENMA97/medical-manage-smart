<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class AppraisalTemplate extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'name_ar',
        'description',
        'department_id',
        'position_id',
        'total_weight',
        'rating_scale',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'total_weight' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // ─── Relationships ───

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(AppraisalCriteria::class, 'template_id');
    }

    // ─── Scopes ───

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
