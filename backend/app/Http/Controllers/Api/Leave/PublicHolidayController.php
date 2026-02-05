<?php

namespace App\Http\Controllers\Api\Leave;

use App\Http\Controllers\Controller;
use App\Models\Leave\PublicHoliday;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicHolidayController extends Controller
{
    /**
     * عرض جميع الإجازات الرسمية
     */
    public function index(Request $request): JsonResponse
    {
        $query = PublicHoliday::query();

        // فلترة حسب السنة
        if ($request->has('year')) {
            $query->whereYear('date', $request->year);
        }

        // فلترة حسب نوع التقويم
        if ($request->has('calendar_type')) {
            $query->where('calendar_type', $request->calendar_type);
        }

        $holidays = $query->orderBy('date')->paginate($request->per_page ?? 50);

        return response()->json([
            'success' => true,
            'data' => $holidays,
        ]);
    }

    /**
     * عرض الإجازات الرسمية لسنة معينة
     */
    public function byYear(int $year): JsonResponse
    {
        $holidays = PublicHoliday::whereYear('date', $year)
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $holidays,
        ]);
    }

    /**
     * عرض تفاصيل إجازة رسمية
     */
    public function show(PublicHoliday $publicHoliday): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $publicHoliday,
        ]);
    }

    /**
     * إنشاء إجازة رسمية جديدة
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:100',
            'name_en' => 'required|string|max:100',
            'date' => 'required|date',
            'hijri_date' => 'nullable|string|max:20',
            'calendar_type' => 'required|in:gregorian,hijri',
            'is_recurring' => 'boolean',
            'number_of_days' => 'required|integer|min:1|max:30',
            'applies_to_all' => 'boolean',
            'applicable_departments' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $holiday = PublicHoliday::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الإجازة الرسمية بنجاح',
            'data' => $holiday,
        ], 201);
    }

    /**
     * تحديث إجازة رسمية
     */
    public function update(Request $request, PublicHoliday $publicHoliday): JsonResponse
    {
        $validated = $request->validate([
            'name_ar' => 'sometimes|required|string|max:100',
            'name_en' => 'sometimes|required|string|max:100',
            'date' => 'sometimes|required|date',
            'hijri_date' => 'nullable|string|max:20',
            'calendar_type' => 'sometimes|required|in:gregorian,hijri',
            'is_recurring' => 'boolean',
            'number_of_days' => 'sometimes|required|integer|min:1|max:30',
            'applies_to_all' => 'boolean',
            'applicable_departments' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $publicHoliday->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الإجازة الرسمية بنجاح',
            'data' => $publicHoliday,
        ]);
    }

    /**
     * حذف إجازة رسمية
     */
    public function destroy(PublicHoliday $publicHoliday): JsonResponse
    {
        $publicHoliday->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الإجازة الرسمية بنجاح',
        ]);
    }
}
