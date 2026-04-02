<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeCertificate;
use App\Models\EmployeeSkill;
use App\Models\SkillCategory;
use App\Models\TrainingCourse;
use App\Models\TrainingEnrollment;
use App\Models\TrainingProgram;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainingController extends Controller
{
    // ─── Training Programs ───

    /**
     * GET /api/training/programs
     */
    public function programs(Request $request): JsonResponse
    {
        $programs = TrainingProgram::with('department')
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('year'), fn($q) => $q->where('year', $request->input('year')))
            ->when($request->filled('department_id'), fn($q) => $q->where('department_id', $request->input('department_id')))
            ->withCount('courses')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json(['success' => true, 'data' => $programs]);
    }

    /**
     * POST /api/training/programs
     */
    public function storeProgram(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'description' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'type' => 'required|in:onboarding,mandatory,professional,leadership,technical,safety,compliance',
            'department_id' => 'nullable|uuid|exists:departments,id',
            'year' => 'required|integer|min:2020',
            'budget' => 'numeric|min:0',
        ]);

        $program = TrainingProgram::create([
            ...$validated,
            'status' => 'planned',
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء البرنامج التدريبي',
            'data' => $program,
        ], 201);
    }

    /**
     * GET /api/training/programs/{id}
     */
    public function showProgram(string $id): JsonResponse
    {
        $program = TrainingProgram::with(['department', 'courses.enrollments'])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $program]);
    }

    // ─── Training Courses ───

    /**
     * GET /api/training/courses
     */
    public function courses(Request $request): JsonResponse
    {
        $courses = TrainingCourse::with('program')
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('program_id'), fn($q) => $q->where('program_id', $request->input('program_id')))
            ->when($request->filled('delivery_method'), fn($q) => $q->where('delivery_method', $request->input('delivery_method')))
            ->withCount('enrollments')
            ->orderBy('start_date', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json(['success' => true, 'data' => $courses]);
    }

    /**
     * POST /api/training/courses
     */
    public function storeCourse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'program_id' => 'nullable|uuid|exists:training_programs,id',
            'title' => 'required|string|max:255',
            'title_ar' => 'required|string|max:255',
            'description' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'objectives' => 'nullable|string',
            'objectives_ar' => 'nullable|string',
            'delivery_method' => 'required|in:classroom,online,blended,on_the_job,self_paced,workshop,conference',
            'provider' => 'nullable|string',
            'provider_ar' => 'nullable|string',
            'trainer_name' => 'nullable|string',
            'location' => 'nullable|string',
            'location_ar' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'duration_hours' => 'required|integer|min:1',
            'max_participants' => 'nullable|integer|min:1',
            'cost_per_person' => 'numeric|min:0',
            'total_cost' => 'numeric|min:0',
            'is_mandatory' => 'boolean',
            'certificate_issued' => 'boolean',
        ]);

        $course = TrainingCourse::create([
            ...$validated,
            'status' => 'scheduled',
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الدورة التدريبية',
            'data' => $course,
        ], 201);
    }

    /**
     * GET /api/training/courses/{id}
     */
    public function showCourse(string $id): JsonResponse
    {
        $course = TrainingCourse::with(['program', 'enrollments.employee'])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $course]);
    }

    // ─── Enrollments ───

    /**
     * POST /api/training/courses/{courseId}/enroll
     */
    public function enroll(string $courseId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|uuid|exists:employees,id',
        ]);

        $existing = TrainingEnrollment::where('course_id', $courseId)
            ->where('employee_id', $validated['employee_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'الموظف مسجل مسبقاً في هذه الدورة',
            ], 422);
        }

        $course = TrainingCourse::findOrFail($courseId);

        if ($course->max_participants) {
            $count = TrainingEnrollment::where('course_id', $courseId)
                ->whereNotIn('status', ['withdrawn', 'no_show'])
                ->count();

            if ($count >= $course->max_participants) {
                return response()->json([
                    'success' => false,
                    'message' => 'الدورة مكتملة العدد',
                ], 422);
            }
        }

        $enrollment = TrainingEnrollment::create([
            'course_id' => $courseId,
            'employee_id' => $validated['employee_id'],
            'status' => 'nominated',
            'nominated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم ترشيح الموظف للدورة',
            'data' => $enrollment->load('employee'),
        ], 201);
    }

    /**
     * PUT /api/training/enrollments/{id}
     */
    public function updateEnrollment(string $id, Request $request): JsonResponse
    {
        $enrollment = TrainingEnrollment::findOrFail($id);

        $validated = $request->validate([
            'status' => 'in:nominated,approved,enrolled,attending,completed,failed,withdrawn,no_show',
            'score' => 'nullable|numeric|min:0',
            'attendance_percentage' => 'nullable|numeric|between:0,100',
            'feedback' => 'nullable|string',
            'rating' => 'nullable|integer|between:1,5',
            'manager_feedback' => 'nullable|string',
        ]);

        if (isset($validated['status']) && $validated['status'] === 'approved') {
            $validated['approved_by'] = auth()->id();
            $validated['approved_at'] = now();
        }

        $enrollment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة التسجيل',
            'data' => $enrollment,
        ]);
    }

    // ─── Employee Certificates ───

    /**
     * GET /api/training/certificates
     */
    public function certificates(Request $request): JsonResponse
    {
        $certificates = EmployeeCertificate::with('employee')
            ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->input('employee_id')))
            ->when($request->filled('type'), fn($q) => $q->where('type', $request->input('type')))
            ->when($request->boolean('expiring_soon'), function ($q) {
                $q->where('expiry_date', '<=', now()->addDays(30))
                    ->where('expiry_date', '>=', now());
            })
            ->orderBy('expiry_date', 'asc')
            ->paginate($request->input('per_page', 15));

        return response()->json(['success' => true, 'data' => $certificates]);
    }

    /**
     * POST /api/training/certificates
     */
    public function storeCertificate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|uuid|exists:employees,id',
            'name' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'type' => 'required|in:professional,technical,medical,safety,language,academic,license',
            'issuing_body' => 'required|string',
            'issuing_body_ar' => 'nullable|string',
            'certificate_number' => 'nullable|string',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'renewal_required' => 'boolean',
            'renewal_reminder_days' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $certificate = EmployeeCertificate::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الشهادة',
            'data' => $certificate,
        ], 201);
    }

    // ─── Skills ───

    /**
     * GET /api/training/skill-categories
     */
    public function skillCategories(): JsonResponse
    {
        $categories = SkillCategory::active()->orderBy('sort_order')->get();

        return response()->json(['success' => true, 'data' => $categories]);
    }

    /**
     * GET /api/training/skills
     */
    public function skills(Request $request): JsonResponse
    {
        $skills = EmployeeSkill::with(['employee', 'skillCategory'])
            ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->input('employee_id')))
            ->when($request->filled('skill_category_id'), fn($q) => $q->where('skill_category_id', $request->input('skill_category_id')))
            ->when($request->filled('proficiency_level'), fn($q) => $q->where('proficiency_level', $request->input('proficiency_level')))
            ->paginate($request->input('per_page', 15));

        return response()->json(['success' => true, 'data' => $skills]);
    }

    /**
     * POST /api/training/skills
     */
    public function storeSkill(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|uuid|exists:employees,id',
            'skill_category_id' => 'required|uuid|exists:skill_categories,id',
            'skill_name' => 'required|string|max:255',
            'skill_name_ar' => 'nullable|string|max:255',
            'proficiency_level' => 'required|in:beginner,intermediate,advanced,expert',
            'years_of_experience' => 'integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $skill = EmployeeSkill::create([
            ...$validated,
            'assessed_by' => auth()->id(),
            'last_assessed_date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة المهارة',
            'data' => $skill,
        ], 201);
    }
}
