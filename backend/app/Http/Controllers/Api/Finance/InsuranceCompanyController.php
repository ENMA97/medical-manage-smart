<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\InsuranceCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InsuranceCompanyController extends Controller
{
    /**
     * قائمة شركات التأمين
     */
    public function index(Request $request): JsonResponse
    {
        $query = InsuranceCompany::query()
            ->withCount('claims')
            ->when($request->type, fn($q, $type) => $q->where('type', $type))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name_ar', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%");
                });
            })
            ->orderBy('name_ar');

        $companies = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->get();

        return response()->json([
            'success' => true,
            'data' => $companies,
        ]);
    }

    /**
     * الشركات النشطة
     */
    public function active(): JsonResponse
    {
        $companies = InsuranceCompany::where('is_active', true)
            ->withValidContract()
            ->orderBy('name_ar')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $companies,
        ]);
    }

    /**
     * إنشاء شركة تأمين
     */
    public function store(Request $request): JsonResponse
    {
        if (Gate::denies('finance.manage')) {
            abort(403, 'غير مصرح لك بإنشاء شركات تأمين');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:insurance_companies,code'],
            'name_ar' => ['required', 'string', 'max:200'],
            'name_en' => ['nullable', 'string', 'max:200'],
            'type' => ['nullable', 'string', 'max:50'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:300'],
            'discount_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
            'contract_start_date' => ['nullable', 'date'],
            'contract_end_date' => ['nullable', 'date', 'after:contract_start_date'],
        ]);

        $company = InsuranceCompany::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء شركة التأمين بنجاح',
            'data' => $company,
        ], 201);
    }

    /**
     * عرض شركة تأمين
     */
    public function show(InsuranceCompany $company): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $company->loadCount('claims'),
        ]);
    }

    /**
     * تحديث شركة تأمين
     */
    public function update(Request $request, InsuranceCompany $company): JsonResponse
    {
        if (Gate::denies('finance.manage')) {
            abort(403, 'غير مصرح لك بتعديل شركات التأمين');
        }

        $validated = $request->validate([
            'code' => ['sometimes', 'string', 'max:20', 'unique:insurance_companies,code,' . $company->id],
            'name_ar' => ['sometimes', 'string', 'max:200'],
            'name_en' => ['nullable', 'string', 'max:200'],
            'type' => ['nullable', 'string', 'max:50'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:300'],
            'discount_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
            'contract_start_date' => ['nullable', 'date'],
            'contract_end_date' => ['nullable', 'date'],
        ]);

        $company->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث شركة التأمين بنجاح',
            'data' => $company->fresh(),
        ]);
    }

    /**
     * حذف شركة تأمين
     */
    public function destroy(InsuranceCompany $company): JsonResponse
    {
        if (Gate::denies('finance.manage')) {
            abort(403, 'غير مصرح لك بحذف شركات التأمين');
        }

        if ($company->claims()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف الشركة لوجود مطالبات مرتبطة بها',
            ], 422);
        }

        $company->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف شركة التأمين بنجاح',
        ]);
    }
}
