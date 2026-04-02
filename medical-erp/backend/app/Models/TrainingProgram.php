<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class TrainingProgram extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'name_ar',
        'description',
        'description_ar',
        'type',
        'department_id',
        'year',
        'budget',
        'actual_cost',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'budget' => 'decimal:2',
            'actual_cost' => 'decimal:2',
        ];
    }

    // ─── Relationships ───

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function courses(): HasMany
    {
        return $this->hasMany(TrainingCourse::class, 'program_id');
    }
}
