<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GeneratedLetter;
use App\Models\LetterTemplate;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LetterController extends Controller
{
    /**
     * GET /api/letter-templates
     */
    public function templates(Request $request): JsonResponse
    {
        $templates = LetterTemplate::query()
            ->when($request->boolean('active_only', true), fn($q) => $q->where('is_active', true))
            ->when($request->filled('type'), fn($q) => $q->where('letter_type', $request->input('type')))
            ->orderBy('name_ar')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $templates,
        ]);
    }

    /**
     * GET /api/letters
     */
    public function index(Request $request): JsonResponse
    {
        $letters = GeneratedLetter::with(['template', 'employee'])
            ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->input('employee_id')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('type'), fn($q) => $q->where('letter_type', $request->input('type')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->input('search');
                $q->where('letter_number', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $letters,
        ]);
    }

    /**
     * POST /api/letters
     * إنشاء خطاب جديد من قالب
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'template_id' => 'required|exists:letter_templates,id',
            'employee_id' => 'required|exists:employees,id',
            'variables' => 'nullable|array',
            'notes' => 'nullable|string|max:500',
        ]);

        $template = LetterTemplate::findOrFail($request->input('template_id'));
        $employee = Employee::with(['department', 'position'])->findOrFail($request->input('employee_id'));

        // بناء المتغيرات الافتراضية
        $variables = array_merge([
            'employee_name' => $employee->full_name_ar ?: $employee->full_name_en,
            'employee_number' => $employee->employee_number,
            'department' => $employee->department?->name_ar,
            'position' => $employee->position?->title_ar,
            'hire_date' => $employee->hire_date?->format('Y-m-d'),
            'national_id' => $employee->national_id,
            'date' => now()->format('Y-m-d'),
            'hijri_date' => now()->format('Y-m-d'), // placeholder
        ], $request->input('variables', []));

        // استبدال المتغيرات في القالب
        $contentAr = $template->body_template_ar ?? $template->body_template ?? '';
        $content = $template->body_template ?? '';

        foreach ($variables as $key => $value) {
            $contentAr = str_replace("{{$key}}", $value ?? '', $contentAr);
            $content = str_replace("{{$key}}", $value ?? '', $content);
        }

        // توليد رقم الخطاب
        $letterNumber = 'LTR-' . now()->format('Y') . '-' . str_pad(
            GeneratedLetter::count() + 1,
            5,
            '0',
            STR_PAD_LEFT
        );

        $letter = GeneratedLetter::create([
            'template_id' => $template->id,
            'employee_id' => $employee->id,
            'letter_number' => $letterNumber,
            'letter_type' => $template->letter_type,
            'content' => $content,
            'content_ar' => $contentAr,
            'variables_used' => $variables,
            'status' => $template->requires_approval ? 'pending' : 'approved',
            'generated_by' => auth()->id(),
            'approved_by' => $template->requires_approval ? null : auth()->id(),
            'approved_at' => $template->requires_approval ? null : now(),
            'notes' => $request->input('notes'),
        ]);

        $letter->load(['template', 'employee']);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الخطاب بنجاح',
            'data' => $letter,
        ], 201);
    }

    /**
     * GET /api/letters/{id}
     */
    public function show(string $id): JsonResponse
    {
        $letter = GeneratedLetter::with(['template', 'employee'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $letter,
        ]);
    }

    /**
     * POST /api/letters/{id}/approve
     */
    public function approve(string $id): JsonResponse
    {
        $letter = GeneratedLetter::findOrFail($id);

        if ($letter->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن اعتماد هذا الخطاب',
            ], 422);
        }

        $letter->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم اعتماد الخطاب',
            'data' => $letter,
        ]);
    }
}
