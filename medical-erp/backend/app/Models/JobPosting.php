<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class JobPosting extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'requisition_id',
        'title',
        'title_ar',
        'description',
        'description_ar',
        'responsibilities',
        'responsibilities_ar',
        'benefits',
        'benefits_ar',
        'channels',
        'publish_date',
        'closing_date',
        'is_internal',
        'status',
        'views_count',
        'applications_count',
        'published_by',
    ];

    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'publish_date' => 'date',
            'closing_date' => 'date',
            'is_internal' => 'boolean',
            'views_count' => 'integer',
            'applications_count' => 'integer',
        ];
    }

    // ─── Relationships ───

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(JobRequisition::class, 'requisition_id');
    }

    public function publishedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'posting_id');
    }
}
