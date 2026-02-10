<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use App\Http\Resources\System\PermissionResource;
use App\Models\System\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class PermissionController extends Controller
{
    /**
     * قائمة الصلاحيات
     */
    public function index(): AnonymousResourceCollection
    {
        if (Gate::denies('system.roles')) {
            abort(403, 'غير مصرح لك بعرض الصلاحيات');
        }

        $permissions = Permission::orderBy('module')
            ->orderBy('name_ar')
            ->get();

        return PermissionResource::collection($permissions);
    }

    /**
     * الصلاحيات مجمعة حسب الوحدة
     */
    public function byModule(): JsonResponse
    {
        if (Gate::denies('system.roles')) {
            abort(403, 'غير مصرح لك بعرض الصلاحيات');
        }

        $permissions = Permission::orderBy('name_ar')->get();

        $grouped = $permissions->groupBy('module')->map(function ($modulePermissions, $module) {
            return [
                'module' => $module,
                'module_name' => $this->getModuleName($module),
                'permissions' => PermissionResource::collection($modulePermissions),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $grouped,
        ]);
    }

    /**
     * الحصول على اسم الوحدة
     */
    private function getModuleName(string $module): array
    {
        $modules = [
            'hr' => ['ar' => 'الموارد البشرية', 'en' => 'Human Resources'],
            'payroll' => ['ar' => 'الرواتب', 'en' => 'Payroll'],
            'roster' => ['ar' => 'الجدولة', 'en' => 'Roster'],
            'inventory' => ['ar' => 'المخزون', 'en' => 'Inventory'],
            'finance' => ['ar' => 'المالية', 'en' => 'Finance'],
            'leaves' => ['ar' => 'الإجازات', 'en' => 'Leaves'],
            'system' => ['ar' => 'النظام', 'en' => 'System'],
        ];

        return $modules[$module] ?? ['ar' => $module, 'en' => $module];
    }
}
