<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'label_ar',
        'description',
        'is_public',
        'is_editable',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'is_editable' => 'boolean',
        ];
    }
}
