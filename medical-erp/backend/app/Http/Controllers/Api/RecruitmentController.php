<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Interview;
use App\Models\JobApplication;
use App\Models\JobOffer;
use App\Models\JobPosting;
use App\Models\JobRequisition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecruitmentController extends Controller
{
    // ─── Job Requisitions ───

    /**
     * GET /api/recruitment/requisitions
     */
    public function requisitions(Request $request): JsonResponse
    {
        $requisitions = JobRequisition::with(['department', 'position', 'requestedByUser'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('department_id'), fn($q) => $q->where('department_id', $request->input('department_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->input('search');
                $q->where('requisition_number', 'like', "%{$search}%");
            })
            ->withCount('postings')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json(['success' => true, 'data' => $requisitions]);
    }

    /**
     * POST /api/recruitment/requisitions
     */
    public function storeRequisition(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'department_id' => 'required|uuid|exists:departments,id',
            'position_id' => 'required|uuid|exists:positions,id',
            'vacancies' => 'integer|min:1',
            'employment_type' => 'required|in:full_time,part_time,contract,internship',
            'priority' => 'required|in:urgent,high,medium,low',
            'justification' => 'required|string',
            'justification_ar' => 'nullable|string',
            'job_description' => 'nullable|string',
            'job_description_ar' => 'nullable|string',
            'requirements' => 'nullable|string',
            'requirements_ar' => 'nullable|string',
            'qualifications' => 'nullable|array',
            'min_experience_years' => 'integer|min:0',
            'salary_range_min' => 'nullable|numeric|min:0',
            'salary_range_max' => 'nullable|numeric|min:0',
            'target_hire_date' => 'nullable|date',
        ]);

        $reqNumber = 'REQ-' . now()->format('Y') . '-' . str_pad(
            JobRequisition::count() + 1, 4, '0', STR_PAD_LEFT
        );

        $requisition = JobRequisition::create([
            ...$validated,
            'requisition_number' => $reqNumber,
            'requested_by' => auth()->id(),
            'status' => 'draft',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء طلب التوظيف',
            'data' => $requisition->load(['department', 'position']),
        ], 201);
    }

    /**
     * GET /api/recruitment/requisitions/{id}
     */
    public function showRequisition(string $id): JsonResponse
    {
        $requisition = JobRequisition::with(['department', 'position', 'requestedByUser', 'postings'])
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $requisition]);
    }

    /**
     * POST /api/recruitment/requisitions/{id}/approve
     */
    public function approveRequisition(string $id): JsonResponse
    {
        $requisition = JobRequisition::findOrFail($id);

        if (!in_array($requisition->status, ['pending', 'draft'])) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن اعتماد هذا الطلب',
            ], 422);
        }

        $requisition->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم اعتماد طلب التوظيف',
            'data' => $requisition,
        ]);
    }

    // ─── Job Postings ───

    /**
     * GET /api/recruitment/postings
     */
    public function postings(Request $request): JsonResponse
    {
        $postings = JobPosting::with('requisition.position')
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->withCount('applications')
            ->orderBy('publish_date', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json(['success' => true, 'data' => $postings]);
    }

    /**
     * POST /api/recruitment/postings
     */
    public function storePosting(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'requisition_id' => 'required|uuid|exists:job_requisitions,id',
            'title' => 'required|string|max:255',
            'title_ar' => 'required|string|max:255',
            'description' => 'required|string',
            'description_ar' => 'nullable|string',
            'responsibilities' => 'nullable|string',
            'responsibilities_ar' => 'nullable|string',
            'benefits' => 'nullable|string',
            'benefits_ar' => 'nullable|string',
            'channels' => 'nullable|array',
            'publish_date' => 'required|date',
            'closing_date' => 'required|date|after:publish_date',
            'is_internal' => 'boolean',
        ]);

        $posting = JobPosting::create([
            ...$validated,
            'status' => 'draft',
            'published_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الإعلان الوظيفي',
            'data' => $posting,
        ], 201);
    }

    /**
     * POST /api/recruitment/postings/{id}/publish
     */
    public function publishPosting(string $id): JsonResponse
    {
        $posting = JobPosting::findOrFail($id);
        $posting->update(['status' => 'published']);

        // تحديث حالة طلب التوظيف إلى "مفتوح"
        $posting->requisition->update(['status' => 'open']);

        return response()->json([
            'success' => true,
            'message' => 'تم نشر الإعلان الوظيفي',
            'data' => $posting,
        ]);
    }

    // ─── Candidates ───

    /**
     * GET /api/recruitment/candidates
     */
    public function candidates(Request $request): JsonResponse
    {
        $candidates = Candidate::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->input('search');
                $q->where(function ($q2) use ($search) {
                    $q2->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->boolean('blacklisted'), fn($q) => $q->where('is_blacklisted', true))
            ->withCount('applications')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json(['success' => true, 'data' => $candidates]);
    }

    /**
     * POST /api/recruitment/candidates
     */
    public function storeCandidate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'first_name_ar' => 'nullable|string|max:255',
            'last_name_ar' => 'nullable|string|max:255',
            'email' => 'required|email|unique:candidates,email',
            'phone' => 'required|string',
            'gender' => 'nullable|in:male,female',
            'date_of_birth' => 'nullable|date',
            'nationality' => 'nullable|string',
            'nationality_ar' => 'nullable|string',
            'national_id' => 'nullable|string',
            'current_employer' => 'nullable|string',
            'current_position' => 'nullable|string',
            'years_of_experience' => 'integer|min:0',
            'expected_salary' => 'nullable|numeric|min:0',
            'current_salary' => 'nullable|numeric|min:0',
            'highest_education' => 'nullable|string',
            'university' => 'nullable|string',
            'major' => 'nullable|string',
            'skills' => 'nullable|array',
            'languages' => 'nullable|array',
            'source' => 'nullable|in:website,linkedin,referral,agency,career_fair,social_media,walk_in,internal,other',
            'referred_by_employee_id' => 'nullable|uuid|exists:employees,id',
            'notes' => 'nullable|string',
        ]);

        $candidate = Candidate::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة المرشح',
            'data' => $candidate,
        ], 201);
    }

    /**
     * GET /api/recruitment/candidates/{id}
     */
    public function showCandidate(string $id): JsonResponse
    {
        $candidate = Candidate::with(['applications.posting', 'offers'])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $candidate]);
    }

    // ─── Job Applications ───

    /**
     * GET /api/recruitment/applications
     */
    public function applications(Request $request): JsonResponse
    {
        $applications = JobApplication::with(['candidate', 'posting'])
            ->when($request->filled('posting_id'), fn($q) => $q->where('posting_id', $request->input('posting_id')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json(['success' => true, 'data' => $applications]);
    }

    /**
     * POST /api/recruitment/applications
     */
    public function storeApplication(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'posting_id' => 'required|uuid|exists:job_postings,id',
            'candidate_id' => 'required|uuid|exists:candidates,id',
            'cover_letter' => 'nullable|string',
        ]);

        $existing = JobApplication::where('posting_id', $validated['posting_id'])
            ->where('candidate_id', $validated['candidate_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'المرشح تقدّم مسبقاً لهذه الوظيفة',
            ], 422);
        }

        $appNumber = 'APP-' . now()->format('Y') . '-' . str_pad(
            JobApplication::count() + 1, 5, '0', STR_PAD_LEFT
        );

        $application = JobApplication::create([
            ...$validated,
            'application_number' => $appNumber,
            'status' => 'received',
        ]);

        // تحديث عدد الطلبات
        JobPosting::where('id', $validated['posting_id'])->increment('applications_count');

        return response()->json([
            'success' => true,
            'message' => 'تم تقديم الطلب',
            'data' => $application->load(['candidate', 'posting']),
        ], 201);
    }

    /**
     * PUT /api/recruitment/applications/{id}/status
     */
    public function updateApplicationStatus(string $id, Request $request): JsonResponse
    {
        $application = JobApplication::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:received,screening,shortlisted,interview_scheduled,interviewed,assessment,reference_check,offer_pending,offered,accepted,rejected,withdrawn,on_hold',
            'screening_score' => 'nullable|numeric|min:0',
            'screening_notes' => 'nullable|string',
            'rejection_reason' => 'nullable|string',
        ]);

        $updateData = ['status' => $validated['status']];

        if ($validated['status'] === 'screening') {
            $updateData['screened_by'] = auth()->id();
            $updateData['screened_at'] = now();
            $updateData['screening_score'] = $validated['screening_score'] ?? null;
            $updateData['screening_notes'] = $validated['screening_notes'] ?? null;
        }

        if ($validated['status'] === 'rejected') {
            $updateData['rejected_by'] = auth()->id();
            $updateData['rejected_at'] = now();
            $updateData['rejection_reason'] = $validated['rejection_reason'] ?? null;
        }

        $application->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الطلب',
            'data' => $application,
        ]);
    }

    // ─── Interviews ───

    /**
     * GET /api/recruitment/interviews
     */
    public function interviews(Request $request): JsonResponse
    {
        $interviews = Interview::with(['application.candidate', 'primaryInterviewer'])
            ->when($request->filled('application_id'), fn($q) => $q->where('application_id', $request->input('application_id')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->orderBy('scheduled_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json(['success' => true, 'data' => $interviews]);
    }

    /**
     * POST /api/recruitment/interviews
     */
    public function storeInterview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'application_id' => 'required|uuid|exists:job_applications,id',
            'round' => 'integer|min:1',
            'type' => 'required|in:phone,video,in_person,panel,technical,hr',
            'scheduled_at' => 'required|date',
            'duration_minutes' => 'integer|min:15',
            'location' => 'nullable|string',
            'interviewers' => 'nullable|array',
            'primary_interviewer_id' => 'required|uuid|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $interview = Interview::create([
            ...$validated,
            'status' => 'scheduled',
        ]);

        // تحديث حالة الطلب
        JobApplication::where('id', $validated['application_id'])
            ->update(['status' => 'interview_scheduled']);

        return response()->json([
            'success' => true,
            'message' => 'تم جدولة المقابلة',
            'data' => $interview->load(['application.candidate', 'primaryInterviewer']),
        ], 201);
    }

    /**
     * PUT /api/recruitment/interviews/{id}/feedback
     */
    public function interviewFeedback(string $id, Request $request): JsonResponse
    {
        $interview = Interview::findOrFail($id);

        $validated = $request->validate([
            'overall_score' => 'nullable|numeric|min:0',
            'recommendation' => 'required|in:strong_hire,hire,maybe,no_hire,strong_no_hire',
            'strengths' => 'nullable|string',
            'concerns' => 'nullable|string',
            'feedback' => 'nullable|string',
        ]);

        $interview->update([
            ...$validated,
            'status' => 'completed',
        ]);

        // تحديث حالة الطلب
        $interview->application->update(['status' => 'interviewed']);

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل نتيجة المقابلة',
            'data' => $interview,
        ]);
    }

    // ─── Job Offers ───

    /**
     * POST /api/recruitment/offers
     */
    public function storeOffer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'application_id' => 'required|uuid|exists:job_applications,id',
            'candidate_id' => 'required|uuid|exists:candidates,id',
            'position_id' => 'required|uuid|exists:positions,id',
            'department_id' => 'required|uuid|exists:departments,id',
            'offered_salary' => 'required|numeric|min:0',
            'allowances' => 'nullable|array',
            'benefits' => 'nullable|array',
            'contract_type' => 'required|in:permanent,fixed_term,probation',
            'contract_duration_months' => 'nullable|integer|min:1',
            'probation_months' => 'integer|min:0',
            'proposed_start_date' => 'required|date',
            'offer_valid_until' => 'required|date|after:today',
            'additional_terms' => 'nullable|string',
            'additional_terms_ar' => 'nullable|string',
        ]);

        $offerNumber = 'OFR-' . now()->format('Y') . '-' . str_pad(
            JobOffer::count() + 1, 4, '0', STR_PAD_LEFT
        );

        $offer = JobOffer::create([
            ...$validated,
            'offer_number' => $offerNumber,
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء عرض العمل',
            'data' => $offer->load(['candidate', 'position', 'department']),
        ], 201);
    }

    /**
     * POST /api/recruitment/offers/{id}/approve
     */
    public function approveOffer(string $id): JsonResponse
    {
        $offer = JobOffer::findOrFail($id);

        if (!in_array($offer->status, ['draft', 'pending_approval'])) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن اعتماد هذا العرض',
            ], 422);
        }

        $offer->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم اعتماد عرض العمل',
            'data' => $offer,
        ]);
    }

    /**
     * POST /api/recruitment/offers/{id}/send
     */
    public function sendOffer(string $id): JsonResponse
    {
        $offer = JobOffer::findOrFail($id);

        if ($offer->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'يجب اعتماد العرض قبل إرساله',
            ], 422);
        }

        $offer->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // تحديث حالة الطلب
        $offer->application->update(['status' => 'offered']);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال عرض العمل للمرشح',
            'data' => $offer,
        ]);
    }

    /**
     * POST /api/recruitment/offers/{id}/respond
     */
    public function respondOffer(string $id, Request $request): JsonResponse
    {
        $offer = JobOffer::findOrFail($id);

        $validated = $request->validate([
            'response' => 'required|in:accepted,declined',
            'decline_reason' => 'nullable|required_if:response,declined|string',
        ]);

        $offer->update([
            'status' => $validated['response'],
            'responded_at' => now(),
            'decline_reason' => $validated['decline_reason'] ?? null,
        ]);

        // تحديث حالة الطلب
        $offer->application->update([
            'status' => $validated['response'] === 'accepted' ? 'accepted' : 'rejected',
        ]);

        $message = $validated['response'] === 'accepted'
            ? 'تم قبول عرض العمل'
            : 'تم رفض عرض العمل';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $offer,
        ]);
    }
}
