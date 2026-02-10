<?php

namespace App\Http\Controllers\Api\Roster;

use App\Http\Controllers\Controller;
use App\Models\Roster\RosterValidationRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ValidationRuleController extends Controller
{
    /**
     * قائمة قواعد التحقق
     */
    public function index(Request $request): JsonResponse
    {
        $query = RosterValidationRule::query()
            ->when($request->type, fn($q, $type) => $q->where('type', $type))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('priority')
            ->orderBy('name');

        $rules = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->get();

        return response()->json([
            'success' => true,
            'data' => $rules,
        ]);
    }

    /**
     * إنشاء قاعدة تحقق جديدة
     */
    public function store(Request $request): JsonResponse
    {
        if (Gate::denies('roster.manage')) {
            abort(403, 'غير مصرح لك بإنشاء قواعد التحقق');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'type' => ['required', 'in:max_hours,min_rest,consecutive_days,skill_coverage,overlap'],
            'rule_config' => ['required', 'array'],
            'error_message_ar' => ['required', 'string', 'max:200'],
            'error_message_en' => ['nullable', 'string', 'max:200'],
            'severity' => ['sometimes', 'in:error,warning,info'],
            'priority' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'applies_to_positions' => ['nullable', 'array'],
            'applies_to_departments' => ['nullable', 'array'],
        ]);

        $rule = RosterValidationRule::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء قاعدة التحقق بنجاح',
            'data' => $rule,
        ], 201);
    }

    /**
     * عرض قاعدة تحقق
     */
    public function show(RosterValidationRule $rule): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $rule,
        ]);
    }

    /**
     * تحديث قاعدة تحقق
     */
    public function update(Request $request, RosterValidationRule $rule): JsonResponse
    {
        if (Gate::denies('roster.manage')) {
            abort(403, 'غير مصرح لك بتعديل قواعد التحقق');
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'type' => ['sometimes', 'in:max_hours,min_rest,consecutive_days,skill_coverage,overlap'],
            'rule_config' => ['sometimes', 'array'],
            'error_message_ar' => ['sometimes', 'string', 'max:200'],
            'error_message_en' => ['nullable', 'string', 'max:200'],
            'severity' => ['sometimes', 'in:error,warning,info'],
            'priority' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'applies_to_positions' => ['nullable', 'array'],
            'applies_to_departments' => ['nullable', 'array'],
        ]);

        $rule->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث قاعدة التحقق بنجاح',
            'data' => $rule->fresh(),
        ]);
    }

    /**
     * حذف قاعدة تحقق
     */
    public function destroy(RosterValidationRule $rule): JsonResponse
    {
        if (Gate::denies('roster.manage')) {
            abort(403, 'غير مصرح لك بحذف قواعد التحقق');
        }

        $rule->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف قاعدة التحقق بنجاح',
        ]);
    }
}
