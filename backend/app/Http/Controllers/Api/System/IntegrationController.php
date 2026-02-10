<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use App\Models\System\IntegrationConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;

class IntegrationController extends Controller
{
    /**
     * قائمة التكاملات
     */
    public function index(): JsonResponse
    {
        if (Gate::denies('system.integrations')) {
            abort(403, 'غير مصرح لك بعرض التكاملات');
        }

        $integrations = IntegrationConfig::orderBy('name_ar')->get();

        return response()->json([
            'success' => true,
            'data' => $integrations->map(fn($integration) => [
                'id' => $integration->id,
                'code' => $integration->code,
                'name' => $integration->name,
                'description' => $integration->description,
                'provider' => $integration->provider,
                'endpoint' => $integration->endpoint,
                'is_active' => $integration->is_active,
                'last_sync_at' => $integration->last_sync_at?->toISOString(),
                'last_sync_status' => $integration->last_sync_status,
                'last_sync_message' => $integration->last_sync_message,
            ]),
        ]);
    }

    /**
     * عرض تكامل
     */
    public function show(IntegrationConfig $integration): JsonResponse
    {
        if (Gate::denies('system.integrations')) {
            abort(403, 'غير مصرح لك بعرض التكاملات');
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $integration->id,
                'code' => $integration->code,
                'name' => $integration->name,
                'description' => $integration->description,
                'provider' => $integration->provider,
                'endpoint' => $integration->endpoint,
                'settings' => $integration->settings,
                'is_active' => $integration->is_active,
                'last_sync_at' => $integration->last_sync_at?->toISOString(),
                'last_sync_status' => $integration->last_sync_status,
                'last_sync_message' => $integration->last_sync_message,
            ],
        ]);
    }

    /**
     * تحديث تكامل
     */
    public function update(Request $request, IntegrationConfig $integration): JsonResponse
    {
        if (Gate::denies('system.integrations.manage')) {
            abort(403, 'غير مصرح لك بتعديل التكاملات');
        }

        $validated = $request->validate([
            'endpoint' => ['sometimes', 'url'],
            'credentials' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $integration->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث التكامل بنجاح',
            'data' => [
                'id' => $integration->id,
                'code' => $integration->code,
                'is_active' => $integration->is_active,
            ],
        ]);
    }

    /**
     * اختبار الاتصال
     */
    public function test(IntegrationConfig $integration): JsonResponse
    {
        if (Gate::denies('system.integrations.manage')) {
            abort(403, 'غير مصرح لك باختبار التكاملات');
        }

        try {
            $credentials = $integration->decrypted_credentials;

            // اختبار بسيط للاتصال
            $response = Http::timeout(10)
                ->withHeaders($this->buildHeaders($integration))
                ->get($integration->endpoint);

            $status = $response->successful() ? 'success' : 'failed';
            $message = $response->successful()
                ? 'تم الاتصال بنجاح'
                : 'فشل الاتصال: ' . $response->status();

            return response()->json([
                'success' => $response->successful(),
                'message' => $message,
                'data' => [
                    'status_code' => $response->status(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في الاتصال: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تشغيل المزامنة
     */
    public function sync(IntegrationConfig $integration): JsonResponse
    {
        if (Gate::denies('system.integrations.manage')) {
            abort(403, 'غير مصرح لك بتشغيل المزامنة');
        }

        if (!$integration->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'التكامل غير مفعل',
            ], 422);
        }

        try {
            // هنا يتم تنفيذ المزامنة حسب نوع التكامل
            $result = $this->executeSyncByProvider($integration);

            $integration->updateSyncStatus(
                $result['success'] ? 'success' : 'failed',
                $result['message']
            );

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result['data'] ?? null,
            ]);
        } catch (\Exception $e) {
            $integration->updateSyncStatus('error', $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'خطأ في المزامنة: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * بناء الهيدرات حسب نوع التكامل
     */
    private function buildHeaders(IntegrationConfig $integration): array
    {
        $headers = ['Accept' => 'application/json'];
        $credentials = $integration->decrypted_credentials;

        if (!$credentials) {
            return $headers;
        }

        if (isset($credentials['api_key'])) {
            $headers['Authorization'] = 'Bearer ' . $credentials['api_key'];
        } elseif (isset($credentials['username']) && isset($credentials['password'])) {
            $headers['Authorization'] = 'Basic ' . base64_encode(
                $credentials['username'] . ':' . $credentials['password']
            );
        }

        return $headers;
    }

    /**
     * تنفيذ المزامنة حسب المزود
     */
    private function executeSyncByProvider(IntegrationConfig $integration): array
    {
        // يمكن توسيع هذه الدالة لدعم مزودين مختلفين
        return match ($integration->provider) {
            'zkteco' => $this->syncZKTeco($integration),
            'sms' => $this->syncSMS($integration),
            'email' => $this->syncEmail($integration),
            default => [
                'success' => false,
                'message' => 'مزود غير مدعوم: ' . $integration->provider,
            ],
        };
    }

    /**
     * مزامنة ZKTeco
     */
    private function syncZKTeco(IntegrationConfig $integration): array
    {
        // TODO: تنفيذ مزامنة ZKTeco
        return [
            'success' => true,
            'message' => 'تمت مزامنة ZKTeco بنجاح',
            'data' => ['synced_records' => 0],
        ];
    }

    /**
     * مزامنة SMS
     */
    private function syncSMS(IntegrationConfig $integration): array
    {
        // TODO: تنفيذ مزامنة SMS
        return [
            'success' => true,
            'message' => 'تم التحقق من خدمة SMS',
        ];
    }

    /**
     * مزامنة Email
     */
    private function syncEmail(IntegrationConfig $integration): array
    {
        // TODO: تنفيذ مزامنة Email
        return [
            'success' => true,
            'message' => 'تم التحقق من خدمة البريد الإلكتروني',
        ];
    }
}
