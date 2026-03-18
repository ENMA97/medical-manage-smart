<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Custody\StoreCustodyRequest;
use App\Models\CustodyItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustodyController extends Controller
{
    /**
     * GET /api/custody
     * قائمة العهد مع التصفية
     */
    public function index(Request $request): JsonResponse
    {
        $custodyItems = CustodyItem::with(['employee'])
            ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->input('employee_id')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('item_type'), fn($q) => $q->where('item_type', $request->input('item_type')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('item_name', 'like', "%{$search}%")
                      ->orWhere('item_name_ar', 'like', "%{$search}%")
                      ->orWhere('serial_number', 'like', "%{$search}%")
                      ->orWhere('asset_tag', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'تم جلب قائمة العهد بنجاح',
            'data' => $custodyItems,
        ]);
    }

    /**
     * POST /api/custody
     * تسليم عهدة لموظف
     */
    public function store(StoreCustodyRequest $request): JsonResponse
    {
        try {
            $data = $request->only([
                'employee_id', 'item_name', 'item_name_ar', 'item_type',
                'serial_number', 'asset_tag', 'description', 'value',
                'condition_on_delivery', 'delivery_date', 'expected_return_date',
                'delivered_by', 'notes',
            ]);

            $data['status'] = 'delivered';

            $custodyItem = CustodyItem::create($data);
            $custodyItem->load('employee');

            return response()->json([
                'success' => true,
                'message' => 'تم تسليم العهدة بنجاح',
                'data' => $custodyItem,
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسليم العهدة',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/custody/{id}
     * عرض تفاصيل عهدة
     */
    public function show(string $id): JsonResponse
    {
        $custodyItem = CustodyItem::with(['employee'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب بيانات العهدة بنجاح',
            'data' => $custodyItem,
        ]);
    }

    /**
     * POST /api/custody/{id}/return
     * إرجاع عهدة
     */
    public function return(Request $request, string $id): JsonResponse
    {
        $custodyItem = CustodyItem::findOrFail($id);

        if ($custodyItem->status === 'returned') {
            return response()->json([
                'success' => false,
                'message' => 'تم إرجاع هذه العهدة بالفعل',
            ], 422);
        }

        $request->validate([
            'condition_on_return' => 'nullable|string',
            'received_by' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $custodyItem->update([
                'status' => 'returned',
                'actual_return_date' => now(),
                'condition_on_return' => $request->input('condition_on_return'),
                'received_by' => $request->input('received_by'),
                'notes' => $request->input('notes', $custodyItem->notes),
            ]);

            $custodyItem->load('employee');

            return response()->json([
                'success' => true,
                'message' => 'تم إرجاع العهدة بنجاح',
                'data' => $custodyItem,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرجاع العهدة',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
