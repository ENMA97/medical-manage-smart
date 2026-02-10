<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Http\Resources\HR\PositionResource;
use App\Models\HR\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class PositionController extends Controller
{
    /**
     * قائمة المناصب
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Position::with(['department'])
            ->withCount('employees')
            ->when($request->department_id, fn($q, $id) => $q->where('department_id', $id))
            ->when($request->is_medical !== null, fn($q) => $q->where('is_medical', $request->boolean('is_medical')))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name_ar', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%");
                });
            })
            ->orderBy('level')
            ->orderBy('name_ar');

        $positions = $request->per_page 
            ? $query->paginate($request->per_page)
            : $query->get();

        return PositionResource::collection($positions);
    }

    /**
     * المناصب النشطة
     */
    public function active(): AnonymousResourceCollection
    {
        $positions = Position::with(['department'])
            ->where('is_active', true)
            ->orderBy('level')
            ->orderBy('name_ar')
            ->get();

        return PositionResource::collection($positions);
    }

    /**
     * المناصب حسب القسم
     */
    public function byDepartment(string $departmentId): AnonymousResourceCollection
    {
        $positions = Position::where('department_id', $departmentId)
            ->where('is_active', true)
            ->orderBy('level')
            ->orderBy('name_ar')
            ->get();

        return PositionResource::collection($positions);
    }

    /**
     * إنشاء منصب جديد
     */
    public function store(Request $request): JsonResponse
    {
        if (Gate::denies('hr.manage')) {
            abort(403, 'غير مصرح لك بإنشاء مناصب');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:positions,code'],
            'name_ar' => ['required', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'description_ar' => ['nullable', 'string', 'max:500'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'level' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'min_salary' => ['nullable', 'numeric', 'min:0'],
            'max_salary' => ['nullable', 'numeric', 'min:0', 'gte:min_salary'],
            'is_medical' => ['sometimes', 'boolean'],
            'requires_license' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $position = Position::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء المنصب بنجاح',
            'data' => new PositionResource($position->load('department')),
        ], 201);
    }

    /**
     * عرض منصب
     */
    public function show(Position $position): PositionResource
    {
        return new PositionResource(
            $position->load('department')->loadCount('employees')
        );
    }

    /**
     * تحديث منصب
     */
    public function update(Request $request, Position $position): JsonResponse
    {
        if (Gate::denies('hr.manage')) {
            abort(403, 'غير مصرح لك بتعديل المناصب');
        }

        $validated = $request->validate([
            'code' => ['sometimes', 'string', 'max:20', 'unique:positions,code,' . $position->id],
            'name_ar' => ['sometimes', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'description_ar' => ['nullable', 'string', 'max:500'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'level' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'min_salary' => ['nullable', 'numeric', 'min:0'],
            'max_salary' => ['nullable', 'numeric', 'min:0'],
            'is_medical' => ['sometimes', 'boolean'],
            'requires_license' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $position->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المنصب بنجاح',
            'data' => new PositionResource($position->fresh('department')),
        ]);
    }

    /**
     * حذف منصب
     */
    public function destroy(Position $position): JsonResponse
    {
        if (Gate::denies('hr.manage')) {
            abort(403, 'غير مصرح لك بحذف المناصب');
        }

        if ($position->employees()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف المنصب لوجود موظفين مرتبطين به',
            ], 422);
        }

        $position->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المنصب بنجاح',
        ]);
    }
}
