<?php

namespace App\Http\Controllers\Api\Roster;

use App\Http\Controllers\Controller;
use App\Models\Roster\BiometricDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BiometricDeviceController extends Controller
{
    /**
     * قائمة أجهزة البصمة
     */
    public function index(Request $request): JsonResponse
    {
        $query = BiometricDevice::query()
            ->when($request->location, fn($q, $loc) => $q->where('location', 'like', "%{$loc}%"))
            ->when($request->is_online !== null, fn($q) => $q->where('is_online', $request->boolean('is_online')))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%");
                });
            })
            ->orderBy('name');

        $devices = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->get();

        return response()->json([
            'success' => true,
            'data' => $devices,
        ]);
    }

    /**
     * الأجهزة المتصلة
     */
    public function online(): JsonResponse
    {
        $devices = BiometricDevice::where('is_online', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $devices,
        ]);
    }

    /**
     * إضافة جهاز جديد
     */
    public function store(Request $request): JsonResponse
    {
        if (Gate::denies('roster.manage')) {
            abort(403, 'غير مصرح لك بإضافة أجهزة');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'serial_number' => ['required', 'string', 'max:50', 'unique:biometric_devices,serial_number'],
            'model' => ['nullable', 'string', 'max:50'],
            'manufacturer' => ['nullable', 'string', 'max:50'],
            'ip_address' => ['required', 'ip', 'unique:biometric_devices,ip_address'],
            'port' => ['sometimes', 'integer', 'min:1', 'max:65535'],
            'location' => ['nullable', 'string', 'max:200'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'is_active' => ['sometimes', 'boolean'],
            'connection_settings' => ['nullable', 'array'],
        ]);

        $validated['is_online'] = false;
        $validated['last_sync_at'] = null;

        $device = BiometricDevice::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الجهاز بنجاح',
            'data' => $device,
        ], 201);
    }

    /**
     * عرض جهاز
     */
    public function show(BiometricDevice $device): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $device->load('department'),
        ]);
    }

    /**
     * تحديث جهاز
     */
    public function update(Request $request, BiometricDevice $device): JsonResponse
    {
        if (Gate::denies('roster.manage')) {
            abort(403, 'غير مصرح لك بتعديل الأجهزة');
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'serial_number' => ['sometimes', 'string', 'max:50', 'unique:biometric_devices,serial_number,' . $device->id],
            'model' => ['nullable', 'string', 'max:50'],
            'manufacturer' => ['nullable', 'string', 'max:50'],
            'ip_address' => ['sometimes', 'ip', 'unique:biometric_devices,ip_address,' . $device->id],
            'port' => ['sometimes', 'integer', 'min:1', 'max:65535'],
            'location' => ['nullable', 'string', 'max:200'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'is_active' => ['sometimes', 'boolean'],
            'connection_settings' => ['nullable', 'array'],
        ]);

        $device->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الجهاز بنجاح',
            'data' => $device->fresh(),
        ]);
    }

    /**
     * مزامنة الجهاز
     */
    public function sync(BiometricDevice $device): JsonResponse
    {
        if (Gate::denies('roster.manage')) {
            abort(403, 'غير مصرح لك بمزامنة الأجهزة');
        }

        if (!$device->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'الجهاز غير نشط',
            ], 422);
        }

        // هنا يتم تنفيذ المزامنة الفعلية مع الجهاز
        // يمكن استخدام ZKTeco SDK أو مكتبة مماثلة

        try {
            // محاكاة المزامنة
            $recordsCount = $this->syncDeviceRecords($device);

            $device->update([
                'last_sync_at' => now(),
                'is_online' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => "تم مزامنة {$recordsCount} سجل بنجاح",
                'data' => [
                    'records_synced' => $recordsCount,
                    'last_sync_at' => $device->last_sync_at,
                ],
            ]);
        } catch (\Exception $e) {
            $device->update(['is_online' => false]);

            return response()->json([
                'success' => false,
                'message' => 'فشل الاتصال بالجهاز',
            ], 500);
        }
    }

    /**
     * اختبار الاتصال
     */
    public function testConnection(BiometricDevice $device): JsonResponse
    {
        if (Gate::denies('roster.manage')) {
            abort(403, 'غير مصرح لك باختبار الاتصال');
        }

        try {
            // هنا يتم اختبار الاتصال الفعلي
            $isConnected = $this->pingDevice($device);

            $device->update(['is_online' => $isConnected]);

            if ($isConnected) {
                return response()->json([
                    'success' => true,
                    'message' => 'الجهاز متصل',
                    'data' => [
                        'is_online' => true,
                        'response_time_ms' => rand(10, 100), // محاكاة
                    ],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'الجهاز غير متصل',
                ], 503);
            }
        } catch (\Exception $e) {
            $device->update(['is_online' => false]);

            return response()->json([
                'success' => false,
                'message' => 'فشل الاتصال بالجهاز: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * مزامنة سجلات الجهاز
     */
    protected function syncDeviceRecords(BiometricDevice $device): int
    {
        // هذه دالة محاكاة - يجب استبدالها بالاتصال الفعلي بالجهاز
        // مثال: استخدام ZKTeco PHP SDK
        return 0;
    }

    /**
     * اختبار ping للجهاز
     */
    protected function pingDevice(BiometricDevice $device): bool
    {
        // محاولة الاتصال بالجهاز عبر socket
        $socket = @fsockopen(
            $device->ip_address,
            $device->port ?? 4370,
            $errno,
            $errstr,
            5 // timeout
        );

        if ($socket) {
            fclose($socket);
            return true;
        }

        return false;
    }
}
