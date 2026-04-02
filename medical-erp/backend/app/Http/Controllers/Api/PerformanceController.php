<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppraisalCriteria;
use App\Models\AppraisalCycle;
use App\Models\AppraisalScore;
use App\Models\AppraisalTemplate;
use App\Models\Employee;
use App\Models\EmployeeAppraisal;
use App\Models\EmployeeGoal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerformanceController extends Controller
{
    // ─── Appraisal Cycles ───

    /**
     * GET /api/performance/cycles
     */
    public function cycles(Request $request): JsonResponse
    {
        $cycles = AppraisalCycle::query()
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->withCount('appraisals')
            ->orderBy('start_date', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json(['success' => true, 'data' => $cycles]);
    }

    /**
     * POST /api/performance/cycles
     */
    public function storeCycle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'type' => 'required|in:annual,semi_annual,quarterly,probation,project_based',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'review_deadline' => 'required|date|after:end_date',
            'description' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'self_evaluation_enabled' => 'boolean',
            'peer_evaluation_enabled' => 'boolean',
        ]);

        $cycle = AppraisalCycle::create([
            ...$validated,
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء دورة التقييم بنجاح',
            'data' => $cycle,
        ], 201);
    }

    /**
     * GET /api/performance/cycles/{id}
     */
    public function showCycle(string $id): JsonResponse
    {
        $cycle = AppraisalCycle::withCount('appraisals')->findOrFail($id);

        return response()->json(['success' => true, 'data' => $cycle]);
    }

    /**
     * PUT /api/performance/cycles/{id}
     */
    public function updateCycle(string $id, Request $request): JsonResponse
    {
        $cycle = AppraisalCycle::findOrFail($id);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'name_ar' => 'string|max:255',
            'status' => 'in:draft,active,in_review,completed,cancelled',
            'review_deadline' => 'date',
        ]);

        $cycle->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث دورة التقييم',
            'data' => $cycle,
        ]);
    }

    // ─── Templates ───

    /**
     * GET /api/performance/templates
     */
    public function templates(Request $request): JsonResponse
    {
        $templates = AppraisalTemplate::with('criteria')
            ->when($request->boolean('active_only'), fn($q) => $q->active())
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'data' => $templates]);
    }

    /**
     * POST /api/performance/templates
     */
    public function storeTemplate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'description' => 'nullable|string',
            'department_id' => 'nullable|uuid|exists:departments,id',
            'position_id' => 'nullable|uuid|exists:positions,id',
            'rating_scale' => 'required|in:scale_5,scale_10,scale_100,descriptive',
            'criteria' => 'required|array|min:1',
            'criteria.*.name' => 'required|string',
            'criteria.*.name_ar' => 'required|string',
            'criteria.*.category' => 'required|string',
            'criteria.*.weight' => 'required|numeric|min:0',
            'criteria.*.max_score' => 'required|numeric|min:1',
        ]);

        $template = DB::transaction(function () use ($validated) {
            $template = AppraisalTemplate::create([
                'name' => $validated['name'],
                'name_ar' => $validated['name_ar'],
                'description' => $validated['description'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'position_id' => $validated['position_id'] ?? null,
                'rating_scale' => $validated['rating_scale'],
                'total_weight' => collect($validated['criteria'])->sum('weight'),
            ]);

            foreach ($validated['criteria'] as $i => $criteriaData) {
                AppraisalCriteria::create([
                    'template_id' => $template->id,
                    'name' => $criteriaData['name'],
                    'name_ar' => $criteriaData['name_ar'],
                    'category' => $criteriaData['category'],
                    'weight' => $criteriaData['weight'],
                    'max_score' => $criteriaData['max_score'],
                    'sort_order' => $i,
                ]);
            }

            return $template;
        });

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء نموذج التقييم',
            'data' => $template->load('criteria'),
        ], 201);
    }

    // ─── Employee Appraisals ───

    /**
     * GET /api/performance/appraisals
     */
    public function appraisals(Request $request): JsonResponse
    {
        $appraisals = EmployeeAppraisal::with(['employee', 'cycle', 'reviewer'])
            ->when($request->filled('cycle_id'), fn($q) => $q->where('cycle_id', $request->input('cycle_id')))
            ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->input('employee_id')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json(['success' => true, 'data' => $appraisals]);
    }

    /**
     * POST /api/performance/appraisals
     * إنشاء تقييم لموظف
     */
    public function storeAppraisal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cycle_id' => 'required|uuid|exists:appraisal_cycles,id',
            'employee_id' => 'required|uuid|exists:employees,id',
            'template_id' => 'required|uuid|exists:appraisal_templates,id',
            'reviewer_id' => 'required|uuid|exists:users,id',
        ]);

        $existing = EmployeeAppraisal::where('cycle_id', $validated['cycle_id'])
            ->where('employee_id', $validated['employee_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'يوجد تقييم مسبق لهذا الموظف في هذه الدورة',
            ], 422);
        }

        $appraisal = DB::transaction(function () use ($validated) {
            $appraisal = EmployeeAppraisal::create([
                ...$validated,
                'status' => 'pending',
            ]);

            // إنشاء سجلات الدرجات لكل معيار
            $criteria = AppraisalCriteria::where('template_id', $validated['template_id'])->get();
            foreach ($criteria as $criterion) {
                AppraisalScore::create([
                    'appraisal_id' => $appraisal->id,
                    'criteria_id' => $criterion->id,
                ]);
            }

            return $appraisal;
        });

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء التقييم بنجاح',
            'data' => $appraisal->load(['employee', 'scores.criteria']),
        ], 201);
    }

    /**
     * GET /api/performance/appraisals/{id}
     */
    public function showAppraisal(string $id): JsonResponse
    {
        $appraisal = EmployeeAppraisal::with(['employee', 'cycle', 'template', 'reviewer', 'scores.criteria'])
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $appraisal]);
    }

    /**
     * PUT /api/performance/appraisals/{id}/scores
     * تحديث درجات التقييم
     */
    public function updateScores(string $id, Request $request): JsonResponse
    {
        $appraisal = EmployeeAppraisal::findOrFail($id);

        $validated = $request->validate([
            'scores' => 'required|array',
            'scores.*.criteria_id' => 'required|uuid',
            'scores.*.manager_score' => 'nullable|numeric|min:0',
            'scores.*.self_score' => 'nullable|numeric|min:0',
            'scores.*.manager_comment' => 'nullable|string',
            'scores.*.self_comment' => 'nullable|string',
            'manager_comments' => 'nullable|string',
            'self_comments' => 'nullable|string',
            'strengths' => 'nullable|string',
            'weaknesses' => 'nullable|string',
            'improvement_plan' => 'nullable|string',
        ]);

        DB::transaction(function () use ($appraisal, $validated) {
            foreach ($validated['scores'] as $scoreData) {
                AppraisalScore::where('appraisal_id', $appraisal->id)
                    ->where('criteria_id', $scoreData['criteria_id'])
                    ->update(array_filter([
                        'manager_score' => $scoreData['manager_score'] ?? null,
                        'self_score' => $scoreData['self_score'] ?? null,
                        'manager_comment' => $scoreData['manager_comment'] ?? null,
                        'self_comment' => $scoreData['self_comment'] ?? null,
                    ], fn($v) => $v !== null));
            }

            $updateData = array_filter([
                'manager_comments' => $validated['manager_comments'] ?? null,
                'self_comments' => $validated['self_comments'] ?? null,
                'strengths' => $validated['strengths'] ?? null,
                'weaknesses' => $validated['weaknesses'] ?? null,
                'improvement_plan' => $validated['improvement_plan'] ?? null,
            ], fn($v) => $v !== null);

            if ($updateData) {
                $appraisal->update($updateData);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الدرجات',
            'data' => $appraisal->load('scores.criteria'),
        ]);
    }

    /**
     * POST /api/performance/appraisals/{id}/complete
     */
    public function completeAppraisal(string $id, Request $request): JsonResponse
    {
        $appraisal = EmployeeAppraisal::with('scores')->findOrFail($id);

        $validated = $request->validate([
            'final_rating' => 'required|in:outstanding,exceeds,meets,needs_improvement,unsatisfactory',
            'hr_comments' => 'nullable|string',
        ]);

        $finalScore = $appraisal->scores->avg('manager_score') ?? $appraisal->scores->avg('final_score');

        $appraisal->update([
            'status' => 'completed',
            'final_score' => $finalScore,
            'final_rating' => $validated['final_rating'],
            'hr_comments' => $validated['hr_comments'] ?? null,
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إكمال التقييم',
            'data' => $appraisal,
        ]);
    }

    // ─── Employee Goals ───

    /**
     * GET /api/performance/goals
     */
    public function goals(Request $request): JsonResponse
    {
        $goals = EmployeeGoal::with('employee')
            ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->input('employee_id')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('cycle_id'), fn($q) => $q->where('cycle_id', $request->input('cycle_id')))
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json(['success' => true, 'data' => $goals]);
    }

    /**
     * POST /api/performance/goals
     */
    public function storeGoal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|uuid|exists:employees,id',
            'cycle_id' => 'nullable|uuid|exists:appraisal_cycles,id',
            'title' => 'required|string|max:255',
            'title_ar' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'type' => 'required|in:performance,development,project,behavioral',
            'priority' => 'required|in:critical,high,medium,low',
            'start_date' => 'required|date',
            'target_date' => 'required|date|after:start_date',
            'weight' => 'numeric|min:0',
            'milestones' => 'nullable|array',
            'key_results' => 'nullable|array',
        ]);

        $goal = EmployeeGoal::create([
            ...$validated,
            'status' => 'draft',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الهدف بنجاح',
            'data' => $goal,
        ], 201);
    }

    /**
     * PUT /api/performance/goals/{id}
     */
    public function updateGoal(string $id, Request $request): JsonResponse
    {
        $goal = EmployeeGoal::findOrFail($id);

        $validated = $request->validate([
            'progress_percentage' => 'integer|between:0,100',
            'status' => 'in:draft,active,on_track,at_risk,behind,completed,cancelled',
            'manager_feedback' => 'nullable|string',
        ]);

        $goal->update($validated);

        if (isset($validated['status']) && $validated['status'] === 'completed') {
            $goal->update(['completed_date' => now(), 'progress_percentage' => 100]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الهدف',
            'data' => $goal,
        ]);
    }
}
