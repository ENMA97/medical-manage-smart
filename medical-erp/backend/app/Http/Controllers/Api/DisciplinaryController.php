<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Disciplinary\AddSessionRequest;
use App\Http\Requests\Disciplinary\FormCommitteeRequest;
use App\Http\Requests\Disciplinary\IssueDecisionRequest;
use App\Http\Requests\Disciplinary\StoreViolationRequest;
use App\Models\CommitteeMember;
use App\Models\DisciplinaryDecision;
use App\Models\InvestigationCommittee;
use App\Models\InvestigationSession;
use App\Models\Violation;
use App\Models\ViolationType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DisciplinaryController extends Controller
{
    // ═══════════════════════════════════════════
    //  أنواع المخالفات - Violation Types
    // ═══════════════════════════════════════════

    /**
     * GET /api/violation-types
     */
    public function violationTypes(Request $request): JsonResponse
    {
        $types = ViolationType::query()
            ->when($request->filled('category'), fn($q) => $q->where('category', $request->input('category')))
            ->when($request->filled('severity'), fn($q) => $q->where('severity', $request->input('severity')))
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }

    /**
     * GET /api/violation-types/{id}/suggest-penalty
     * اقتراح العقوبة التلقائي بناءً على نوع المخالفة وعدد التكرارات
     */
    public function suggestPenalty(Request $request, string $id): JsonResponse
    {
        $type = ViolationType::findOrFail($id);

        $employeeId = $request->input('employee_id');
        $occurrenceNumber = 1;

        if ($employeeId) {
            // حساب عدد المخالفات السابقة من نفس النوع لنفس الموظف
            $occurrenceNumber = Violation::where('employee_id', $employeeId)
                ->where('violation_type_id', $id)
                ->whereIn('status', ['decided', 'closed'])
                ->count() + 1;
        }

        $suggested = $type->suggestPenalty($occurrenceNumber);

        return response()->json([
            'success' => true,
            'data' => [
                'violation_type' => $type,
                'occurrence_number' => $occurrenceNumber,
                'suggested_penalty' => $suggested,
                'labor_law_article' => $type->labor_law_article,
                'severity' => $type->severity,
                'requires_investigation' => $type->requires_investigation,
                'all_penalties' => $type->penalties,
            ],
        ]);
    }

    // ═══════════════════════════════════════════
    //  المخالفات - Violations
    // ═══════════════════════════════════════════

    /**
     * GET /api/violations
     */
    public function index(Request $request): JsonResponse
    {
        $violations = Violation::with(['employee', 'violationType', 'reporter', 'latestDecision'])
            ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->input('employee_id')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('violation_type_id'), fn($q) => $q->where('violation_type_id', $request->input('violation_type_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->input('search');
                $q->where(function ($query) use ($search) {
                    $query->where('violation_number', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('description_ar', 'like', "%{$search}%")
                        ->orWhereHas('employee', function ($eq) use ($search) {
                            $eq->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('first_name_ar', 'like', "%{$search}%")
                                ->orWhere('last_name_ar', 'like', "%{$search}%")
                                ->orWhere('employee_number', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $violations,
        ]);
    }

    /**
     * POST /api/violations
     */
    public function store(StoreViolationRequest $request): JsonResponse
    {
        $type = ViolationType::findOrFail($request->input('violation_type_id'));

        // حساب رقم التكرار
        $occurrenceNumber = Violation::where('employee_id', $request->input('employee_id'))
            ->where('violation_type_id', $request->input('violation_type_id'))
            ->whereIn('status', ['decided', 'closed'])
            ->count() + 1;

        $violation = Violation::create([
            'violation_number' => 'VIO-' . date('Y') . '-' . str_pad(Violation::withTrashed()->count() + 1, 5, '0', STR_PAD_LEFT),
            'employee_id' => $request->input('employee_id'),
            'violation_type_id' => $request->input('violation_type_id'),
            'violation_date' => $request->input('violation_date'),
            'violation_time' => $request->input('violation_time'),
            'location' => $request->input('location'),
            'description' => $request->input('description'),
            'description_ar' => $request->input('description_ar'),
            'occurrence_number' => $occurrenceNumber,
            'status' => $type->requires_investigation ? 'under_investigation' : 'reported',
            'reported_by' => auth()->id(),
            'witnesses' => $request->input('witnesses'),
        ]);

        $violation->load(['employee', 'violationType', 'reporter']);

        // إرفاق العقوبة المقترحة
        $suggestedPenalty = $type->suggestPenalty($occurrenceNumber);

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل المخالفة بنجاح',
            'data' => $violation,
            'suggested_penalty' => $suggestedPenalty,
        ], 201);
    }

    /**
     * GET /api/violations/{id}
     */
    public function show(string $id): JsonResponse
    {
        $violation = Violation::with([
            'employee', 'violationType', 'reporter',
            'committee.members.employee', 'committee.sessions',
            'decisions.decidedBy', 'decisions.approvedBy',
        ])->findOrFail($id);

        // العقوبة المقترحة
        $suggestedPenalty = $violation->violationType->suggestPenalty($violation->occurrence_number);

        return response()->json([
            'success' => true,
            'data' => $violation,
            'suggested_penalty' => $suggestedPenalty,
        ]);
    }

    // ═══════════════════════════════════════════
    //  لجان التحقيق - Investigation Committees
    // ═══════════════════════════════════════════

    /**
     * POST /api/violations/{violationId}/committee
     * تشكيل لجنة تحقيق
     */
    public function formCommittee(FormCommitteeRequest $request, string $violationId): JsonResponse
    {
        $violation = Violation::findOrFail($violationId);

        if ($violation->committee) {
            return response()->json([
                'success' => false,
                'message' => 'تم تشكيل لجنة تحقيق مسبقاً لهذه المخالفة',
            ], 422);
        }

        $committee = InvestigationCommittee::create([
            'committee_number' => 'COM-' . date('Y') . '-' . str_pad(InvestigationCommittee::count() + 1, 4, '0', STR_PAD_LEFT),
            'name' => $request->input('name'),
            'name_ar' => $request->input('name_ar'),
            'violation_id' => $violationId,
            'chairman_id' => $request->input('chairman_id'),
            'formation_date' => now()->toDateString(),
            'deadline' => $request->input('deadline'),
            'status' => 'formed',
            'mandate' => $request->input('mandate'),
            'mandate_ar' => $request->input('mandate_ar'),
            'formed_by' => auth()->id(),
        ]);

        // إضافة الأعضاء
        foreach ($request->input('members') as $member) {
            CommitteeMember::create([
                'committee_id' => $committee->id,
                'employee_id' => $member['employee_id'],
                'role' => $member['role'],
                'role_ar' => $member['role_ar'] ?? null,
            ]);
        }

        $violation->update(['status' => 'under_investigation']);

        $committee->load(['members.employee', 'chairman', 'violation']);

        return response()->json([
            'success' => true,
            'message' => 'تم تشكيل لجنة التحقيق بنجاح',
            'data' => $committee,
        ], 201);
    }

    /**
     * GET /api/committees/{id}
     */
    public function showCommittee(string $id): JsonResponse
    {
        $committee = InvestigationCommittee::with([
            'members.employee', 'chairman', 'violation.employee',
            'violation.violationType', 'sessions', 'formedBy',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $committee,
        ]);
    }

    /**
     * POST /api/committees/{id}/sessions
     * إضافة جلسة تحقيق
     */
    public function addSession(AddSessionRequest $request, string $committeeId): JsonResponse
    {
        $committee = InvestigationCommittee::findOrFail($committeeId);

        $sessionNumber = $committee->sessions()->count() + 1;

        $session = InvestigationSession::create([
            'committee_id' => $committeeId,
            'session_number' => $sessionNumber,
            'session_date' => $request->input('session_date'),
            'location' => $request->input('location'),
            'agenda' => $request->input('agenda'),
            'agenda_ar' => $request->input('agenda_ar'),
            'minutes' => $request->input('minutes'),
            'minutes_ar' => $request->input('minutes_ar'),
            'employee_response' => $request->input('employee_response'),
            'employee_response_ar' => $request->input('employee_response_ar'),
            'employee_attended' => $request->input('employee_attended'),
            'employee_absence_reason' => $request->input('employee_absence_reason'),
            'status' => $request->input('status'),
        ]);

        if ($committee->status === 'formed') {
            $committee->update(['status' => 'in_progress']);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة جلسة التحقيق بنجاح',
            'data' => $session,
        ], 201);
    }

    // ═══════════════════════════════════════════
    //  القرارات التأديبية - Decisions
    // ═══════════════════════════════════════════

    /**
     * GET /api/decisions
     */
    public function decisions(Request $request): JsonResponse
    {
        $decisions = DisciplinaryDecision::with(['violation.violationType', 'employee', 'decidedBy'])
            ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->input('employee_id')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('penalty_type'), fn($q) => $q->where('penalty_type', $request->input('penalty_type')))
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $decisions,
        ]);
    }

    /**
     * POST /api/violations/{violationId}/decision
     * إصدار قرار تأديبي
     */
    public function issueDecision(IssueDecisionRequest $request, string $violationId): JsonResponse
    {
        $violation = Violation::with('violationType')->findOrFail($violationId);

        $suggestedPenalty = $violation->violationType->suggestPenalty($violation->occurrence_number);

        $decision = DisciplinaryDecision::create([
            'decision_number' => 'DEC-' . date('Y') . '-' . str_pad(DisciplinaryDecision::withTrashed()->count() + 1, 5, '0', STR_PAD_LEFT),
            'violation_id' => $violationId,
            'committee_id' => $violation->committee?->id,
            'employee_id' => $violation->employee_id,
            'penalty_type' => $request->input('penalty_type'),
            'penalty_type_ar' => $request->input('penalty_type_ar'),
            'penalty_details' => $request->input('penalty_details'),
            'penalty_details_ar' => $request->input('penalty_details_ar'),
            'deduction_amount' => $request->input('deduction_amount'),
            'deduction_days' => $request->input('deduction_days'),
            'suspension_days' => $request->input('suspension_days'),
            'effective_date' => $request->input('effective_date'),
            'end_date' => $request->input('end_date'),
            'justification' => $request->input('justification'),
            'justification_ar' => $request->input('justification_ar'),
            'labor_law_reference' => $violation->violationType->labor_law_article,
            'suggested_penalty' => $suggestedPenalty['penalty'] ?? null,
            'suggested_penalty_ar' => $suggestedPenalty['penalty_ar'] ?? null,
            'status' => 'issued',
            'decided_by' => auth()->id(),
            'decided_at' => now(),
            'notes' => $request->input('notes'),
        ]);

        $violation->update(['status' => 'decided']);

        if ($violation->committee) {
            $violation->committee->update(['status' => 'completed']);
        }

        $decision->load(['violation.violationType', 'employee', 'decidedBy']);

        return response()->json([
            'success' => true,
            'message' => 'تم إصدار القرار التأديبي بنجاح',
            'data' => $decision,
        ], 201);
    }

    /**
     * POST /api/decisions/{id}/approve
     */
    public function approveDecision(string $id): JsonResponse
    {
        $decision = DisciplinaryDecision::findOrFail($id);

        if ($decision->status !== 'issued') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن اعتماد هذا القرار في حالته الحالية',
            ], 422);
        }

        $decision->update([
            'status' => 'final',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $decision->load(['violation.violationType', 'employee']);

        return response()->json([
            'success' => true,
            'message' => 'تم اعتماد القرار بنجاح',
            'data' => $decision,
        ]);
    }

    /**
     * GET /api/decisions/{id}
     */
    public function showDecision(string $id): JsonResponse
    {
        $decision = DisciplinaryDecision::with([
            'violation.violationType', 'violation.employee',
            'committee.members.employee', 'employee',
            'decidedBy', 'approvedBy',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $decision,
        ]);
    }
}
