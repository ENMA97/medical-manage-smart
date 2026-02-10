<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use App\Models\Finance\InsuranceClaim;
use App\Models\HR\Contract;
use App\Models\HR\Employee;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\WarehouseStock;
use App\Models\Leave\LeaveRequest;
use App\Models\System\PurchaseRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * ملخص لوحة المعلومات
     */
    public function summary(): JsonResponse
    {
        $data = [
            'employees' => [
                'total' => Employee::count(),
                'active' => Employee::where('is_active', true)->count(),
            ],
            'leaves' => [
                'pending' => LeaveRequest::whereIn('status', ['pending_supervisor', 'pending_admin_manager', 'pending_hr'])->count(),
            ],
            'claims' => [
                'pending' => InsuranceClaim::pending()->count(),
                'total_outstanding' => InsuranceClaim::unpaid()->sum(DB::raw('approved_amount - COALESCE(paid_amount, 0)')),
            ],
            'inventory' => [
                'low_stock_items' => $this->getLowStockCount(),
                'expiring_soon' => $this->getExpiringSoonCount(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * مؤشرات الموارد البشرية
     */
    public function hrMetrics(): JsonResponse
    {
        $data = [
            'employees' => [
                'total' => Employee::count(),
                'active' => Employee::where('is_active', true)->count(),
                'inactive' => Employee::where('is_active', false)->count(),
                'by_department' => Employee::where('is_active', true)
                    ->select('department_id', DB::raw('COUNT(*) as count'))
                    ->groupBy('department_id')
                    ->with('department:id,name_ar')
                    ->get(),
            ],
            'contracts' => [
                'expiring_this_month' => Contract::where('is_active', true)
                    ->where('is_indefinite', false)
                    ->whereBetween('end_date', [now()->startOfMonth(), now()->endOfMonth()])
                    ->count(),
                'expiring_next_month' => Contract::where('is_active', true)
                    ->where('is_indefinite', false)
                    ->whereBetween('end_date', [now()->addMonth()->startOfMonth(), now()->addMonth()->endOfMonth()])
                    ->count(),
            ],
            'leaves' => [
                'pending_requests' => LeaveRequest::whereIn('status', ['pending_supervisor', 'pending_admin_manager', 'pending_hr'])->count(),
                'approved_today' => LeaveRequest::where('status', 'form_completed')
                    ->whereDate('updated_at', today())
                    ->count(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * مؤشرات المالية
     */
    public function financeMetrics(): JsonResponse
    {
        $data = [
            'claims' => [
                'pending_count' => InsuranceClaim::pending()->count(),
                'pending_amount' => InsuranceClaim::pending()->sum('claimed_amount'),
                'approved_this_month' => InsuranceClaim::whereIn('status', ['approved', 'partially_approved'])
                    ->whereMonth('approval_date', now()->month)
                    ->sum('approved_amount'),
                'collected_this_month' => InsuranceClaim::whereMonth('payment_date', now()->month)
                    ->sum('paid_amount'),
            ],
            'outstanding' => [
                'total' => InsuranceClaim::unpaid()
                    ->sum(DB::raw('approved_amount - COALESCE(paid_amount, 0)')),
                'aging' => $this->getAgingSummary(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * تنبيهات المخزون
     */
    public function inventoryAlerts(): JsonResponse
    {
        $lowStockItems = WarehouseStock::select('item_id', DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('item_id')
            ->with('item:id,name_ar,name_en,sku,reorder_level')
            ->get()
            ->filter(fn($stock) => $stock->item && $stock->total_quantity <= $stock->item->reorder_level)
            ->take(10)
            ->map(fn($stock) => [
                'item' => $stock->item,
                'current_quantity' => $stock->total_quantity,
                'reorder_level' => $stock->item->reorder_level,
            ]);

        $expiringItems = WarehouseStock::where('expiry_date', '<=', now()->addDays(30))
            ->where('expiry_date', '>', now())
            ->where('quantity', '>', 0)
            ->with(['item:id,name_ar,name_en,sku', 'warehouse:id,name_ar'])
            ->orderBy('expiry_date')
            ->limit(10)
            ->get()
            ->map(fn($stock) => [
                'item' => $stock->item,
                'warehouse' => $stock->warehouse,
                'quantity' => $stock->quantity,
                'expiry_date' => $stock->expiry_date->toDateString(),
                'days_until_expiry' => $stock->expiry_date->diffInDays(now()),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'low_stock' => $lowStockItems,
                'expiring_soon' => $expiringItems,
            ],
        ]);
    }

    /**
     * الموافقات المعلقة
     */
    public function pendingApprovals(): JsonResponse
    {
        $user = auth()->user();

        $data = [
            'leave_requests' => LeaveRequest::whereIn('status', ['pending_supervisor', 'pending_admin_manager', 'pending_hr'])
                ->count(),
            'purchase_requests' => PurchaseRequest::whereIn('status', ['pending', 'manager_approved', 'finance_approved'])
                ->count(),
            'insurance_claims' => InsuranceClaim::pending()->count(),
        ];

        $data['total'] = array_sum($data);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * عدد الأصناف منخفضة المخزون
     */
    private function getLowStockCount(): int
    {
        return WarehouseStock::select('item_id', DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('item_id')
            ->with('item:id,reorder_level')
            ->get()
            ->filter(fn($stock) => $stock->item && $stock->total_quantity <= $stock->item->reorder_level)
            ->count();
    }

    /**
     * عدد الأصناف التي ستنتهي قريباً
     */
    private function getExpiringSoonCount(): int
    {
        return WarehouseStock::where('expiry_date', '<=', now()->addDays(30))
            ->where('expiry_date', '>', now())
            ->where('quantity', '>', 0)
            ->distinct('item_id')
            ->count('item_id');
    }

    /**
     * ملخص التقادم
     */
    private function getAgingSummary(): array
    {
        $buckets = [
            ['label' => '0-30', 'min' => 0, 'max' => 30],
            ['label' => '31-60', 'min' => 31, 'max' => 60],
            ['label' => '61-90', 'min' => 61, 'max' => 90],
            ['label' => '90+', 'min' => 91, 'max' => 9999],
        ];

        $result = [];

        foreach ($buckets as $bucket) {
            $amount = InsuranceClaim::unpaid()
                ->whereNotNull('submission_date')
                ->whereRaw('DATEDIFF(CURRENT_DATE, submission_date) BETWEEN ? AND ?', [$bucket['min'], $bucket['max']])
                ->sum(DB::raw('approved_amount - COALESCE(paid_amount, 0)'));

            $result[$bucket['label']] = round($amount, 2);
        }

        return $result;
    }
}
