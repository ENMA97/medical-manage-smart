<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with('county.region');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('name_ar', 'like', "%{$search}%")
                  ->orWhere('employee_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('county_id')) {
            $query->where('county_id', $request->input('county_id'));
        }

        $employees = $query->orderBy('name')->paginate($request->input('per_page', 15));

        return response()->json($employees);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_number' => 'required|string|unique:employees',
            'name' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'email' => 'required|email|unique:employees',
            'phone' => 'nullable|string|max:20',
            'national_id' => 'required|string|unique:employees',
            'birth_date' => 'nullable|date',
            'gender' => 'required|in:male,female',
            'nationality' => 'nullable|string|max:100',
            'department_id' => 'nullable|uuid',
            'position_id' => 'nullable|uuid',
            'hire_date' => 'required|date',
            'status' => 'in:active,inactive,suspended,terminated',
            'bank_name' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:34',
            'address' => 'nullable|string',
            'county_id' => 'nullable|uuid|exists:counties,id',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
        ]);

        $employee = Employee::create($validated);
        $employee->load('county.region');

        return response()->json(['data' => $employee], 201);
    }

    public function show(Employee $employee): JsonResponse
    {
        $employee->load('county.region');

        return response()->json(['data' => $employee]);
    }

    public function update(Request $request, Employee $employee): JsonResponse
    {
        $validated = $request->validate([
            'employee_number' => 'sometimes|string|unique:employees,employee_number,' . $employee->id,
            'name' => 'sometimes|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'email' => 'sometimes|email|unique:employees,email,' . $employee->id,
            'phone' => 'nullable|string|max:20',
            'national_id' => 'sometimes|string|unique:employees,national_id,' . $employee->id,
            'birth_date' => 'nullable|date',
            'gender' => 'sometimes|in:male,female',
            'nationality' => 'nullable|string|max:100',
            'department_id' => 'nullable|uuid',
            'position_id' => 'nullable|uuid',
            'hire_date' => 'sometimes|date',
            'status' => 'in:active,inactive,suspended,terminated',
            'bank_name' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:34',
            'address' => 'nullable|string',
            'county_id' => 'nullable|uuid|exists:counties,id',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
        ]);

        $employee->update($validated);
        $employee->load('county.region');

        return response()->json(['data' => $employee]);
    }

    public function destroy(Employee $employee): JsonResponse
    {
        $employee->delete();

        return response()->json(null, 204);
    }
}
