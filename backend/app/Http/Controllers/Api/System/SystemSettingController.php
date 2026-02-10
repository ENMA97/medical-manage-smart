<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use App\Models\System\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SystemSettingController extends Controller
{
    /**
     * قائمة الإعدادات
     */
    public function index(Request $request): JsonResponse
    {
        // للمستخدمين المصرح لهم فقط
        if (Gate::denies('system.settings')) {
            // إرجاع الإعدادات العامة فقط
            $settings = SystemSetting::public()->get();
        } else {
            $settings = SystemSetting::when($request->group, fn($q, $group) => $q->where('group', $group))
                ->orderBy('group')
                ->orderBy('key')
                ->get();
        }

        return response()->json([
            'success' => true,
            'data' => $settings->map(fn($setting) => [
                'id' => $setting->id,
                'group' => $setting->group,
                'key' => $setting->key,
                'value' => $setting->typed_value,
                'type' => $setting->type,
                'name' => $setting->name,
                'description' => $setting->description,
                'is_public' => $setting->is_public,
            ]),
        ]);
    }

    /**
     * إعدادات مجموعة معينة
     */
    public function byGroup(string $group): JsonResponse
    {
        if (Gate::denies('system.settings')) {
            $settings = SystemSetting::byGroup($group)->public()->get();
        } else {
            $settings = SystemSetting::byGroup($group)
                ->orderBy('key')
                ->get();
        }

        return response()->json([
            'success' => true,
            'data' => $settings->map(fn($setting) => [
                'id' => $setting->id,
                'key' => $setting->key,
                'value' => $setting->typed_value,
                'type' => $setting->type,
                'name' => $setting->name,
                'description' => $setting->description,
            ]),
        ]);
    }

    /**
     * عرض إعداد
     */
    public function show(string $key): JsonResponse
    {
        $setting = SystemSetting::where('key', $key)->firstOrFail();

        if (!$setting->is_public && Gate::denies('system.settings')) {
            abort(403, 'غير مصرح لك بعرض هذا الإعداد');
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $setting->id,
                'group' => $setting->group,
                'key' => $setting->key,
                'value' => $setting->typed_value,
                'type' => $setting->type,
                'name' => $setting->name,
                'description' => $setting->description,
            ],
        ]);
    }

    /**
     * تحديث إعداد
     */
    public function update(Request $request, string $key): JsonResponse
    {
        if (Gate::denies('system.settings.manage')) {
            abort(403, 'غير مصرح لك بتعديل الإعدادات');
        }

        $setting = SystemSetting::where('key', $key)->firstOrFail();

        $validated = $request->validate([
            'value' => ['required'],
        ]);

        $value = $validated['value'];

        // التحقق من النوع
        if ($setting->type === 'json' && is_array($value)) {
            $value = json_encode($value);
        } elseif ($setting->type === 'boolean') {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
        }

        $setting->update(['value' => $value]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الإعداد بنجاح',
            'data' => [
                'key' => $setting->key,
                'value' => $setting->fresh()->typed_value,
            ],
        ]);
    }

    /**
     * تحديث عدة إعدادات
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        if (Gate::denies('system.settings.manage')) {
            abort(403, 'غير مصرح لك بتعديل الإعدادات');
        }

        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.key' => ['required', 'string'],
            'settings.*.value' => ['required'],
        ]);

        $updated = [];

        foreach ($validated['settings'] as $settingData) {
            $setting = SystemSetting::where('key', $settingData['key'])->first();

            if ($setting) {
                $value = $settingData['value'];

                if ($setting->type === 'json' && is_array($value)) {
                    $value = json_encode($value);
                } elseif ($setting->type === 'boolean') {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
                }

                $setting->update(['value' => $value]);
                $updated[] = $setting->key;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الإعدادات بنجاح',
            'data' => [
                'updated_keys' => $updated,
            ],
        ]);
    }
}
