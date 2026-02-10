<?php

namespace App\Http\Controllers\Api\Roster;

use App\Http\Controllers\Controller;
use App\Http\Resources\Roster\ShiftPatternResource;
use App\Models\Roster\ShiftPattern;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ShiftPatternController extends Controller
{
    /**
     * قائمة أنماط الورديات
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ShiftPattern::query()
            ->withCount('assignments')
            ->when($request->type, fn($q, $type) => $q->where('type', $type))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name_ar', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%");
                });
            })
            ->orderBy('start_time');

        $patterns = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->get();

        return ShiftPatternResource::collection($patterns);
    }

    /**
     * الأنماط النشطة
     */
    public function active(): AnonymousResourceCollection
    {
        $patterns = ShiftPattern::where('is_active', true)
            ->orderBy('start_time')
            ->get();

        return ShiftPatternResource::collection($patterns);
    }

    /**
     * إنشاء نمط وردية جديد
     */
    public function store(Request $request): JsonResponse
    {
        if (Gate::denies('roster.manage')) {
            abort(403, 'غير مصرح لك بإنشاء أنماط ورديات');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:shift_patterns,code'],
            'name_ar' => ['required', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'type' => ['required', 'in:morning,evening,night,split'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'break_start' => ['nullable', 'date_format:H:i'],
            'break_end' => ['nullable', 'date_format:H:i'],
            'break_duration_minutes' => ['nullable', 'numeric', 'min:0', 'max:120'],
            'scheduled_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'color_code' => ['nullable', 'string', 'max:7'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $pattern = ShiftPattern::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء نمط الوردية بنجاح',
            'data' => new ShiftPatternResource($pattern),
        ], 201);
    }

    /**
     * عرض نمط وردية
     */
    public function show(ShiftPattern $shiftPattern): ShiftPatternResource
    {
        return new ShiftPatternResource(
            $shiftPattern->loadCount('assignments')
        );
    }

    /**
     * تحديث نمط وردية
     */
    public function update(Request $request, ShiftPattern $shiftPattern): JsonResponse
    {
        if (Gate::denies('roster.manage')) {
            abort(403, 'غير مصرح لك بتعديل أنماط الورديات');
        }

        $validated = $request->validate([
            'code' => ['sometimes', 'string', 'max:20', 'unique:shift_patterns,code,' . $shiftPattern->id],
            'name_ar' => ['sometimes', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'type' => ['sometimes', 'in:morning,evening,night,split'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i'],
            'break_start' => ['nullable', 'date_format:H:i'],
            'break_end' => ['nullable', 'date_format:H:i'],
            'break_duration_minutes' => ['nullable', 'numeric', 'min:0', 'max:120'],
            'scheduled_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'color_code' => ['nullable', 'string', 'max:7'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $shiftPattern->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث نمط الوردية بنجاح',
            'data' => new ShiftPatternResource($shiftPattern->fresh()),
        ]);
    }

    /**
     * حذف نمط وردية
     */
    public function destroy(ShiftPattern $shiftPattern): JsonResponse
    {
        if (Gate::denies('roster.manage')) {
            abort(403, 'غير مصرح لك بحذف أنماط الورديات');
        }

        // التحقق من عدم وجود تعيينات مرتبطة
        if ($shiftPattern->assignments()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف النمط لوجود تعيينات مرتبطة به',
            ], 422);
        }

        $shiftPattern->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف نمط الوردية بنجاح',
        ]);
    }
}
