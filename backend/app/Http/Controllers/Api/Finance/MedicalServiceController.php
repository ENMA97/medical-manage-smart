<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\MedicalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MedicalServiceController extends Controller
{
    /**
     * قائمة الخدمات الطبية
     */
    public function index(Request $request): JsonResponse
    {
        $query = MedicalService::with(['costCenter'])
            ->when($request->category, fn($q, $cat) => $q->where('category', $cat))
            ->when($request->cost_center_id, fn($q, $id) => $q->where('cost_center_id', $id))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->requires_doctor !== null, fn($q) => $q->where('requires_doctor', $request->boolean('requires_doctor')))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name_ar', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%");
                });
            })
            ->orderBy('category')
            ->orderBy('name_ar');

        $services = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->get();

        return response()->json([
            'success' => true,
            'data' => $services,
        ]);
    }

    /**
     * الخدمات النشطة
     */
    public function active(): JsonResponse
    {
        $services = MedicalService::where('is_active', true)
            ->orderBy('category')
            ->orderBy('name_ar')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $services,
        ]);
    }

    /**
     * الخدمات حسب الفئة
     */
    public function byCategory(string $category): JsonResponse
    {
        $services = MedicalService::where('category', $category)
            ->where('is_active', true)
            ->orderBy('name_ar')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $services,
        ]);
    }

    /**
     * إنشاء خدمة
     */
    public function store(Request $request): JsonResponse
    {
        if (Gate::denies('finance.manage')) {
            abort(403, 'غير مصرح لك بإنشاء خدمات');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:medical_services,code'],
            'name_ar' => ['required', 'string', 'max:200'],
            'name_en' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:500'],
            'category' => ['required', 'string', 'max:50'],
            'cost_center_id' => ['nullable', 'uuid', 'exists:cost_centers,id'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'insurance_price' => ['nullable', 'numeric', 'min:0'],
            'doctor_commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'requires_doctor' => ['sometimes', 'boolean'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
        ]);

        $service = MedicalService::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الخدمة بنجاح',
            'data' => $service->load('costCenter'),
        ], 201);
    }

    /**
     * عرض خدمة
     */
    public function show(MedicalService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $service->load(['costCenter', 'doctors']),
        ]);
    }

    /**
     * تحديث خدمة
     */
    public function update(Request $request, MedicalService $service): JsonResponse
    {
        if (Gate::denies('finance.manage')) {
            abort(403, 'غير مصرح لك بتعديل الخدمات');
        }

        $validated = $request->validate([
            'code' => ['sometimes', 'string', 'max:20', 'unique:medical_services,code,' . $service->id],
            'name_ar' => ['sometimes', 'string', 'max:200'],
            'name_en' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:500'],
            'category' => ['sometimes', 'string', 'max:50'],
            'cost_center_id' => ['nullable', 'uuid', 'exists:cost_centers,id'],
            'base_price' => ['sometimes', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'insurance_price' => ['nullable', 'numeric', 'min:0'],
            'doctor_commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'requires_doctor' => ['sometimes', 'boolean'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
        ]);

        $service->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الخدمة بنجاح',
            'data' => $service->fresh('costCenter'),
        ]);
    }

    /**
     * حذف خدمة
     */
    public function destroy(MedicalService $service): JsonResponse
    {
        if (Gate::denies('finance.manage')) {
            abort(403, 'غير مصرح لك بحذف الخدمات');
        }

        $service->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الخدمة بنجاح',
        ]);
    }
}
