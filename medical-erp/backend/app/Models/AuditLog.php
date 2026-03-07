<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_name',
        'ip_address',
        'user_agent',
        'auditable_type',
        'auditable_id',
        'event',
        'old_values',
        'new_values',
        'description',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    // ─── Relationships ───

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
