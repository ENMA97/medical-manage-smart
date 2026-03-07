<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class GeneratedLetter extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'template_id',
        'employee_id',
        'letter_number',
        'letter_type',
        'content',
        'content_ar',
        'variables_used',
        'generated_file_path',
        'status',
        'generated_by',
        'approved_by',
        'approved_at',
        'printed_at',
        'delivered_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'variables_used' => 'array',
            'approved_at' => 'datetime',
            'printed_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    // ─── Relationships ───

    public function template(): BelongsTo
    {
        return $this->belongsTo(LetterTemplate::class, 'template_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
