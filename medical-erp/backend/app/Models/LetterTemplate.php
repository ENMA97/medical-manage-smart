<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class LetterTemplate extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'code',
        'name',
        'name_ar',
        'letter_type',
        'body_template',
        'body_template_ar',
        'header_template',
        'footer_template',
        'available_variables',
        'default_settings',
        'requires_approval',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'available_variables' => 'array',
            'default_settings' => 'array',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // ─── Relationships ───

    public function generatedLetters(): HasMany
    {
        return $this->hasMany(GeneratedLetter::class, 'template_id');
    }
}
