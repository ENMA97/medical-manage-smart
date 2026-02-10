<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use App\Http\Resources\System\UserResource;
use App\Models\System\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * قائمة المستخدمين
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        if (Gate::denies('system.users')) {
            abort(403, 'غير مصرح لك بعرض المستخدمين');
        }

        $query = User::with(['employee', 'roles'])
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->role_id, fn($q, $roleId) => $q->whereHas('roles', fn($rq) => $rq->where('roles.id', $roleId)))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('name_ar', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%");
                });
            })
            ->orderBy('name_ar');

        $users = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->get();

        return UserResource::collection($users);
    }

    /**
     * المستخدمين النشطين
     */
    public function active(): AnonymousResourceCollection
    {
        $users = User::active()
            ->with(['employee'])
            ->orderBy('name_ar')
            ->get();

        return UserResource::collection($users);
    }

    /**
     * إنشاء مستخدم
     */
    public function store(Request $request): JsonResponse
    {
        if (Gate::denies('system.users.manage')) {
            abort(403, 'غير مصرح لك بإنشاء مستخدمين');
        }

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:50', 'unique:users,username', 'regex:/^[a-zA-Z0-9_]+$/'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', Password::min(8)->mixedCase()->numbers()],
            'name_ar' => ['required', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'employee_id' => ['nullable', 'uuid', 'exists:employees,id'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['uuid', 'exists:roles,id'],
            'is_active' => ['sometimes', 'boolean'],
            'must_change_password' => ['sometimes', 'boolean'],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        if (!empty($validated['role_ids'])) {
            $user->roles()->attach($validated['role_ids'], [
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء المستخدم بنجاح',
            'data' => new UserResource($user->load(['employee', 'roles'])),
        ], 201);
    }

    /**
     * عرض مستخدم
     */
    public function show(User $user): UserResource
    {
        if (Gate::denies('system.users')) {
            abort(403, 'غير مصرح لك بعرض المستخدمين');
        }

        return new UserResource($user->load(['employee', 'roles.permissions']));
    }

    /**
     * تحديث مستخدم
     */
    public function update(Request $request, User $user): JsonResponse
    {
        if (Gate::denies('system.users.manage')) {
            abort(403, 'غير مصرح لك بتعديل المستخدمين');
        }

        $validated = $request->validate([
            'username' => ['sometimes', 'string', 'max:50', 'unique:users,username,' . $user->id, 'regex:/^[a-zA-Z0-9_]+$/'],
            'email' => ['sometimes', 'email', 'unique:users,email,' . $user->id],
            'name_ar' => ['sometimes', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'employee_id' => ['nullable', 'uuid', 'exists:employees,id'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['uuid', 'exists:roles,id'],
        ]);

        $user->update($validated);

        if (array_key_exists('role_ids', $validated)) {
            $user->roles()->sync(
                collect($validated['role_ids'])->mapWithKeys(fn($roleId) => [
                    $roleId => [
                        'assigned_by' => auth()->id(),
                        'assigned_at' => now(),
                    ]
                ])->toArray()
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المستخدم بنجاح',
            'data' => new UserResource($user->fresh(['employee', 'roles'])),
        ]);
    }

    /**
     * حذف مستخدم
     */
    public function destroy(User $user): JsonResponse
    {
        if (Gate::denies('system.users.manage')) {
            abort(403, 'غير مصرح لك بحذف المستخدمين');
        }

        // لا يمكن حذف المستخدم الحالي
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك حذف حسابك الخاص',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المستخدم بنجاح',
        ]);
    }

    /**
     * تفعيل مستخدم
     */
    public function activate(User $user): JsonResponse
    {
        if (Gate::denies('system.users.manage')) {
            abort(403, 'غير مصرح لك بتفعيل المستخدمين');
        }

        $user->update(['is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'تم تفعيل المستخدم بنجاح',
            'data' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * تعطيل مستخدم
     */
    public function deactivate(User $user): JsonResponse
    {
        if (Gate::denies('system.users.manage')) {
            abort(403, 'غير مصرح لك بتعطيل المستخدمين');
        }

        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك تعطيل حسابك الخاص',
            ], 422);
        }

        $user->update(['is_active' => false]);

        // إنهاء جلسات المستخدم
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم تعطيل المستخدم بنجاح',
            'data' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * إعادة تعيين كلمة المرور
     */
    public function resetPassword(Request $request, User $user): JsonResponse
    {
        if (Gate::denies('system.users.manage')) {
            abort(403, 'غير مصرح لك بإعادة تعيين كلمات المرور');
        }

        $validated = $request->validate([
            'new_password' => ['sometimes', Password::min(8)->mixedCase()->numbers()],
        ]);

        // إذا لم يتم تقديم كلمة مرور جديدة، يتم توليد واحدة
        $newPassword = $validated['new_password'] ?? Str::random(12);

        $user->update([
            'password' => Hash::make($newPassword),
            'must_change_password' => true,
        ]);

        // إنهاء جميع الجلسات
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم إعادة تعيين كلمة المرور بنجاح',
            'data' => [
                'temporary_password' => isset($validated['new_password']) ? null : $newPassword,
            ],
        ]);
    }
}
