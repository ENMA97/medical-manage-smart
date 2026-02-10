<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\CostCenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CostCenterController extends Controller
{
    /**
     * قائمة مراكز التكلفة
     */
    public function index(Request $request): JsonResponse
    {
        $query = CostCenter::with(['parent', 'department'])
            ->when($request->parent_id, fn($q, $id) => $q->where('parent_id', $id))
            ->when($request->department_id, fn($q, $id) => $q->where('department_id', $id))
            ->when($request->type, fn($q, $type) => $q->where('type', $type))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name_ar', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name_ar');

        $costCenters = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->get();

        return response()->json([
            'success' => true,
            'data' => $costCenters,
        ]);
    }

    /**
     * مراكز التكلفة النشطة
     */
    public function active(): JsonResponse
    {
        $costCenters = CostCenter::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $costCenters,
        ]);
    }

    /**
     * إنشاء مركز تكلفة
     */
    public function store(Request $request): JsonResponse
    {
        if (Gate::denies('finance.manage')) {
            abort(403, 'غير مصرح لك بإنشاء مراكز تكلفة');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:cost_centers,code'],
            'name_ar' => ['required', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'parent_id' => ['nullable', 'uuid', 'exists:cost_centers,id'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'type' => ['nullable', 'string', 'max:50'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
        ]);

        $costCenter = CostCenter::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء مركز التكلفة بنجاح',
            'data' => $costCenter->load(['parent', 'department']),
        ], 201);
    }

    /**
     * عرض مركز تكلفة
     */
    public function show(CostCenter $costCenter): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $costCenter->load(['parent', 'children', 'department']),
        ]);
    }

    /**
     * تحديث مركز تكلفة
     */
    public function update(Request $request, CostCenter $costCenter): JsonResponse
    {
        if (Gate::denies('finance.manage')) {
            abort(403, 'غير مصرح لك بتعديل مراكز التكلفة');
        }

        $validated = $request->validate([
            'code' => ['sometimes', 'string', 'max:20', 'unique:cost_centers,code,' . $costCenter->id],
            'name_ar' => ['sometimes', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'parent_id' => ['nullable', 'uuid', 'exists:cost_centers,id'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'type' => ['nullable', 'string', 'max:50'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
        ]);

        if (isset($validated['parent_id']) && $validated['parent_id'] === $costCenter->id) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن جعل مركز التكلفة تابعاً لنفسه',
            ], 422);
        }

        $costCenter->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث مركز التكلفة بنجاح',
            'data' => $costCenter->fresh(['parent', 'department']),
        ]);
    }

    /**
     * حذف مركز تكلفة
     */
    public function destroy(CostCenter $costCenter): JsonResponse
    {
        if (Gate::denies('finance.manage')) {
            abort(403, 'غير مصرح لك بحذف مراكز التكلفة');
        }

        if ($costCenter->children()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف مركز تكلفة له مراكز فرعية',
            ], 422);
        }

        $costCenter->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف مركز التكلفة بنجاح',
        ]);
    }
}
