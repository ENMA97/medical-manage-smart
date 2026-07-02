<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\UpdateSettingRequest;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    /**
     * GET /api/settings
     * قائمة جميع إعدادات النظام
     */
    public function index(Request $request): JsonResponse
    {
        $settings = SystemSetting::query()
            ->when($request->filled('group'), fn($q) => $q->where('group', $request->input('group')))
            ->when($request->filled('is_public'), fn($q) => $q->where('is_public', $request->boolean('is_public')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('key', 'like', "%{$search}%")
                      ->orWhere('label', 'like', "%{$search}%")
                      ->orWhere('label_ar', 'like', "%{$search}%");
                });
            })
            ->orderBy('group')
            ->orderBy('key')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'تم جلب إعدادات النظام بنجاح',
            'data' => $settings,
        ]);
    }

    /**
     * PUT /api/settings/{id}
     * تحديث إعداد
     */
    public function update(UpdateSettingRequest $request, string $id): JsonResponse
    {
        $setting = SystemSetting::findOrFail($id);

        if (!$setting->is_editable) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تعديل هذا الإعداد',
            ], 403);
        }

        try {
            $setting->update([
                'value' => $request->input('value'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الإعداد بنجاح',
                'data' => $setting,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الإعداد',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
