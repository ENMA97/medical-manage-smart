<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\CommissionAdjustment;
use App\Models\Finance\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DoctorController extends Controller
{
    /**
     * قائمة الأطباء
     */
    public function index(Request $request): JsonResponse
    {
        $query = Doctor::with(['employee', 'department'])
            ->when($request->department_id, fn($q, $id) => $q->where('department_id', $id))
            ->when($request->is_consultant !== null, fn($q) => $q->where('is_consultant', $request->boolean('is_consultant')))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('doctor_number', 'like', "%{$search}%")
                        ->orWhere('license_number', 'like', "%{$search}%")
                        ->orWhere('specialization_ar', 'like', "%{$search}%")
                        ->orWhereHas('employee', function ($eq) use ($search) {
                            $eq->where('first_name_ar', 'like', "%{$search}%")
                                ->orWhere('last_name_ar', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('doctor_number');

        $doctors = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->get();

        return response()->json([
            'success' => true,
            'data' => $doctors,
        ]);
    }

    /**
     * الأطباء النشطين
     */
    public function active(): JsonResponse
    {
        $doctors = Doctor::with(['employee'])
            ->where('is_active', true)
            ->orderBy('doctor_number')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $doctors,
        ]);
    }

    /**
     * إنشاء طبيب
     */
    public function store(Request $request): JsonResponse
    {
        if (Gate::denies('finance.manage')) {
            abort(403, 'غير مصرح لك بإنشاء أطباء');
        }

        $validated = $request->validate([
            'employee_id' => ['required', 'uuid', 'exists:employees,id', 'unique:doctors,employee_id'],
            'doctor_number' => ['required', 'string', 'max:20', 'unique:doctors,doctor_number'],
            'license_number' => ['required', 'string', 'max:50', 'unique:doctors,license_number'],
            'specialization_ar' => ['required', 'string', 'max:100'],
            'specialization_en' => ['nullable', 'string', 'max:100'],
            'qualification' => ['nullable', 'string', 'max:200'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'consultation_fee' => ['nullable', 'numeric', 'min:0'],
            'is_consultant' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $doctor = Doctor::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الطبيب بنجاح',
            'data' => $doctor->load(['employee', 'department']),
        ], 201);
    }

    /**
     * عرض طبيب
     */
    public function show(Doctor $doctor): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $doctor->load(['employee', 'department', 'services']),
        ]);
    }

    /**
     * تحديث طبيب
     */
    public function update(Request $request, Doctor $doctor): JsonResponse
    {
        if (Gate::denies('finance.manage')) {
            abort(403, 'غير مصرح لك بتعديل الأطباء');
        }

        $validated = $request->validate([
            'doctor_number' => ['sometimes', 'string', 'max:20', 'unique:doctors,doctor_number,' . $doctor->id],
            'license_number' => ['sometimes', 'string', 'max:50', 'unique:doctors,license_number,' . $doctor->id],
            'specialization_ar' => ['sometimes', 'string', 'max:100'],
            'specialization_en' => ['nullable', 'string', 'max:100'],
            'qualification' => ['nullable', 'string', 'max:200'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'consultation_fee' => ['nullable', 'numeric', 'min:0'],
            'is_consultant' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $doctor->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الطبيب بنجاح',
            'data' => $doctor->fresh(['employee', 'department']),
        ]);
    }

    /**
     * حذف طبيب
     */
    public function destroy(Doctor $doctor): JsonResponse
    {
        if (Gate::denies('finance.manage')) {
            abort(403, 'غير مصرح لك بحذف الأطباء');
        }

        $doctor->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الطبيب بنجاح',
        ]);
    }

    /**
     * خدمات الطبيب
     */
    public function services(Doctor $doctor): JsonResponse
    {
        $services = $doctor->services()
            ->with(['costCenter'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $services,
        ]);
    }

    /**
     * عمولات الطبيب
     */
    public function commissions(Doctor $doctor, Request $request): JsonResponse
    {
        $query = CommissionAdjustment::where('doctor_id', $doctor->id)
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->type, fn($q, $type) => $q->where('type', $type))
            ->when($request->date_from, fn($q, $date) => $q->where('created_at', '>=', $date))
            ->when($request->date_to, fn($q, $date) => $q->where('created_at', '<=', $date . ' 23:59:59'))
            ->orderBy('created_at', 'desc');

        $commissions = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->get();

        return response()->json([
            'success' => true,
            'data' => $commissions,
        ]);
    }
}
