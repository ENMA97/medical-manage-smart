<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    /**
     * GET /api/positions
     */
    public function index(Request $request): JsonResponse
    {
        $positions = Position::withCount('employees')
            ->with('department')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('title_ar', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('department_id'), fn($q) => $q->where('department_id', $request->input('department_id')))
            ->when($request->filled('is_active'), fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('sort_order')
            ->orderBy('title')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $positions,
        ]);
    }

    /**
     * POST /api/positions
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:positions,code',
            'title' => 'required|string|max:100',
            'title_ar' => 'required|string|max:100',
            'department_id' => 'required|uuid|exists:departments,id',
            'category' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'requirements' => 'nullable|string|max:2000',
            'min_salary' => 'nullable|numeric|min:0',
            'max_salary' => 'nullable|numeric|min:0|gte:min_salary',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $position = Position::create($request->only([
            'code', 'title', 'title_ar', 'department_id', 'category',
            'description', 'requirements', 'min_salary', 'max_salary',
            'is_active', 'sort_order',
        ]));

        $position->load('department');

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء المسمى الوظيفي بنجاح',
            'data' => $position,
        ], 201);
    }

    /**
     * GET /api/positions/{id}
     */
    public function show(string $id): JsonResponse
    {
        $position = Position::with(['department', 'employees'])
            ->withCount('employees')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $position,
        ]);
    }

    /**
     * PUT /api/positions/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $position = Position::findOrFail($id);

        $request->validate([
            'code' => "sometimes|required|string|max:20|unique:positions,code,{$id}",
            'title' => 'sometimes|required|string|max:100',
            'title_ar' => 'sometimes|required|string|max:100',
            'department_id' => 'sometimes|required|uuid|exists:departments,id',
            'category' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'requirements' => 'nullable|string|max:2000',
            'min_salary' => 'nullable|numeric|min:0',
            'max_salary' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $position->update($request->only([
            'code', 'title', 'title_ar', 'department_id', 'category',
            'description', 'requirements', 'min_salary', 'max_salary',
            'is_active', 'sort_order',
        ]));

        $position->load('department');

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المسمى الوظيفي بنجاح',
            'data' => $position,
        ]);
    }

    /**
     * DELETE /api/positions/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $position = Position::withCount('employees')->findOrFail($id);

        if ($position->employees_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف مسمى وظيفي مرتبط بموظفين',
            ], 422);
        }

        $position->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المسمى الوظيفي بنجاح',
        ]);
    }
}
