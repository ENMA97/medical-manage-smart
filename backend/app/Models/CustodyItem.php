<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class CustodyItem extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $table = 'custody_management';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'employee_id',
        'item_name',
        'item_name_ar',
        'item_type',
        'serial_number',
        'asset_tag',
        'description',
        'value',
        'condition_on_delivery',
        'condition_on_return',
        'delivery_date',
        'expected_return_date',
        'actual_return_date',
        'status',
        'delivered_by',
        'received_by',
        'notes',
        'delivery_document',
        'return_document',
    ];

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'expected_return_date' => 'date',
            'actual_return_date' => 'date',
            'value' => 'decimal:2',
        ];
    }

    // ─── Relationships ───

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
