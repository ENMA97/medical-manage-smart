<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use App\Http\Resources\System\RoleResource;
use App\Models\System\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class RoleController extends Controller
{
    /**
     * قائمة الأدوار
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        if (Gate::denies('system.roles')) {
            abort(403, 'غير مصرح لك بعرض الأدوار');
        }

        $query = Role::with('permissions')
            ->withCount('users')
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name_ar', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%");
                });
            })
            ->orderBy('is_system', 'desc')
            ->orderBy('name_ar');

        $roles = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->get();

        return RoleResource::collection($roles);
    }

    /**
     * إنشاء دور
     */
    public function store(Request $request): JsonResponse
    {
        if (Gate::denies('system.roles.manage')) {
            abort(403, 'غير مصرح لك بإنشاء أدوار');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:roles,code', 'regex:/^[a-z_]+$/'],
            'name_ar' => ['required', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'description_ar' => ['nullable', 'string', 'max:500'],
            'description_en' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['uuid', 'exists:permissions,id'],
        ]);

        $validated['is_system'] = false;

        $role = Role::create($validated);

        if (!empty($validated['permission_ids'])) {
            $role->permissions()->attach($validated['permission_ids']);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الدور بنجاح',
            'data' => new RoleResource($role->load('permissions')),
        ], 201);
    }

    /**
     * عرض دور
     */
    public function show(Role $role): RoleResource
    {
        if (Gate::denies('system.roles')) {
            abort(403, 'غير مصرح لك بعرض الأدوار');
        }

        return new RoleResource($role->load('permissions')->loadCount('users'));
    }

    /**
     * تحديث دور
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        if (Gate::denies('system.roles.manage')) {
            abort(403, 'غير مصرح لك بتعديل الأدوار');
        }

        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تعديل الأدوار الأساسية للنظام',
            ], 422);
        }

        $validated = $request->validate([
            'code' => ['sometimes', 'string', 'max:50', 'unique:roles,code,' . $role->id, 'regex:/^[a-z_]+$/'],
            'name_ar' => ['sometimes', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'description_ar' => ['nullable', 'string', 'max:500'],
            'description_en' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $role->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الدور بنجاح',
            'data' => new RoleResource($role->fresh('permissions')),
        ]);
    }

    /**
     * حذف دور
     */
    public function destroy(Role $role): JsonResponse
    {
        if (Gate::denies('system.roles.manage')) {
            abort(403, 'غير مصرح لك بحذف الأدوار');
        }

        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف الأدوار الأساسية للنظام',
            ], 422);
        }

        if ($role->users()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف دور مرتبط بمستخدمين',
            ], 422);
        }

        $role->permissions()->detach();
        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الدور بنجاح',
        ]);
    }

    /**
     * تعيين صلاحيات للدور
     */
    public function assignPermissions(Request $request, Role $role): JsonResponse
    {
        if (Gate::denies('system.roles.manage')) {
            abort(403, 'غير مصرح لك بتعديل صلاحيات الأدوار');
        }

        if ($role->is_system && $role->code !== 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تعديل صلاحيات الأدوار الأساسية للنظام',
            ], 422);
        }

        $validated = $request->validate([
            'permission_ids' => ['required', 'array'],
            'permission_ids.*' => ['uuid', 'exists:permissions,id'],
        ]);

        $role->permissions()->sync($validated['permission_ids']);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث صلاحيات الدور بنجاح',
            'data' => new RoleResource($role->fresh('permissions')),
        ]);
    }
}
