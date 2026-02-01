<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class County extends Model
{
    use HasUuids;

    protected $fillable = [
        'region_id',
        'name',
        'name_ar',
        'code',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
