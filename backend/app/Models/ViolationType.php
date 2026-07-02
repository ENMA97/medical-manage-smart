<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ViolationType extends Model
{
    use HasFactory, HasUuids, Auditable;

    protected $fillable = [
        'code', 'name', 'name_ar', 'category', 'category_ar',
        'description', 'description_ar', 'labor_law_article',
        'severity', 'penalties', 'requires_investigation',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'penalties' => 'array',
        'requires_investigation' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function violations(): HasMany
    {
        return $this->hasMany(Violation::class);
    }

    /**
     * اقتراح العقوبة بناءً على رقم التكرار
     */
    public function suggestPenalty(int $occurrenceNumber): ?array
    {
        $penalties = $this->penalties;
        if (!$penalties || empty($penalties)) {
            return null;
        }

        // penalties structure: [
        //   { "occurrence": 1, "penalty": "verbal_warning", "penalty_ar": "إنذار شفهي", ... },
        //   { "occurrence": 2, "penalty": "written_warning", "penalty_ar": "إنذار كتابي", ... },
        //   ...
        // ]
        $match = collect($penalties)->firstWhere('occurrence', $occurrenceNumber);

        if (!$match) {
            // إذا تجاوز عدد التكرارات، نأخذ آخر عقوبة (الأشد)
            $match = collect($penalties)->last();
        }

        return $match;
    }
}
