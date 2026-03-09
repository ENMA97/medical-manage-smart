<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiAnalysisLog;
use App\Models\AiPrediction;
use App\Models\AiRecommendation;
use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\TurnoverRiskScore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiInsightsController extends Controller
{
    /**
     * GET /api/ai/dashboard
     * لوحة تحكم الذكاء الاصطناعي - ملخص شامل
     */
    public function dashboard(): JsonResponse
    {
        $predictions = AiPrediction::where('prediction_date', '>=', now())
            ->where('is_acknowledged', false)
            ->orderBy('impact_level', 'desc')
            ->limit(5)
            ->get();

        $recommendations = AiRecommendation::whereIn('status', ['new', 'under_review'])
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->limit(5)
            ->get();

        $highRiskEmployees = TurnoverRiskScore::with('employee:id,first_name,last_name,first_name_ar,last_name_ar,employee_number')
            ->where('is_latest', true)
            ->whereIn('risk_level', ['high', 'very_high'])
            ->orderBy('risk_score', 'desc')
            ->limit(10)
            ->get();

        $recentAnalyses = AiAnalysisLog::where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'تم جلب لوحة تحكم الذكاء الاصطناعي',
            'data' => [
                'active_predictions' => $predictions,
                'pending_recommendations' => $recommendations,
                'high_risk_employees' => $highRiskEmployees,
                'recent_analyses' => $recentAnalyses,
                'summary' => [
                    'total_predictions' => AiPrediction::where('prediction_date', '>=', now())->count(),
                    'unacknowledged_predictions' => AiPrediction::where('is_acknowledged', false)->count(),
                    'pending_recommendations' => AiRecommendation::whereIn('status', ['new', 'under_review'])->count(),
                    'high_risk_count' => TurnoverRiskScore::where('is_latest', true)->whereIn('risk_level', ['high', 'very_high'])->count(),
                ],
            ],
        ]);
    }

    /**
     * POST /api/ai/analyze/leave-patterns
     * تحليل أنماط الإجازات
     */
    public function analyzeLeavePatterns(): JsonResponse
    {
        $startTime = microtime(true);

        $log = AiAnalysisLog::create([
            'analysis_type' => 'leave_pattern',
            'input_parameters' => ['period' => 'last_12_months'],
            'results' => [],
            'status' => 'processing',
            'triggered_by' => auth()->id(),
            'created_at' => now(),
        ]);

        // تحليل أنماط الإجازات حسب الشهر
        $monthlyPatterns = LeaveRequest::where('status', 'approved')
            ->where('start_date', '>=', now()->subYear())
            ->selectRaw('MONTH(start_date) as month, COUNT(*) as count, SUM(total_days) as total_days')
            ->groupBy(DB::raw('MONTH(start_date)'))
            ->orderBy('month')
            ->get();

        // تحليل الأقسام الأكثر إجازات
        $departmentPatterns = LeaveRequest::where('status', 'approved')
            ->where('start_date', '>=', now()->subYear())
            ->join('employees', 'leave_requests.employee_id', '=', 'employees.id')
            ->join('departments', 'employees.department_id', '=', 'departments.id')
            ->selectRaw('departments.id as dept_id, departments.name_ar as dept_name, COUNT(*) as count, SUM(leave_requests.total_days) as total_days')
            ->groupBy('departments.id', 'departments.name_ar')
            ->orderBy('total_days', 'desc')
            ->get();

        // تحليل أنماط أيام الأسبوع
        $dayOfWeekPatterns = LeaveRequest::where('status', 'approved')
            ->where('start_date', '>=', now()->subYear())
            ->selectRaw('DAYOFWEEK(start_date) as day_of_week, COUNT(*) as count')
            ->groupBy(DB::raw('DAYOFWEEK(start_date)'))
            ->orderBy('count', 'desc')
            ->get();

        $processingTime = (int) ((microtime(true) - $startTime) * 1000);

        $results = [
            'monthly_patterns' => $monthlyPatterns,
            'department_patterns' => $departmentPatterns,
            'day_of_week_patterns' => $dayOfWeekPatterns,
            'peak_month' => $monthlyPatterns->sortByDesc('count')->first(),
            'busiest_department' => $departmentPatterns->first(),
        ];

        $log->update([
            'results' => $results,
            'status' => 'completed',
            'data_points_analyzed' => LeaveRequest::where('start_date', '>=', now()->subYear())->count(),
            'processing_time_ms' => $processingTime,
            'confidence_score' => 0.85,
            'completed_at' => now(),
        ]);

        // توليد توصيات تلقائية بناءً على التحليل
        if ($monthlyPatterns->isNotEmpty()) {
            $peakMonth = $monthlyPatterns->sortByDesc('count')->first();
            $monthNames = ['', 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];

            AiRecommendation::create([
                'analysis_log_id' => $log->id,
                'recommendation_type' => 'scheduling',
                'title' => "Plan for peak leave in month {$peakMonth->month}",
                'title_ar' => "التخطيط لذروة الإجازات في شهر {$monthNames[$peakMonth->month]}",
                'description' => "Analysis shows month {$peakMonth->month} has the highest leave requests ({$peakMonth->count} requests). Consider staffing adjustments.",
                'description_ar' => "يُظهر التحليل أن شهر {$monthNames[$peakMonth->month]} يشهد أعلى طلبات إجازة ({$peakMonth->count} طلب). يُنصح بتعديل خطط التوظيف.",
                'priority' => $peakMonth->count > 20 ? 'high' : 'medium',
                'supporting_data' => $results,
                'status' => 'new',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحليل أنماط الإجازات بنجاح',
            'data' => [
                'analysis_id' => $log->id,
                'results' => $results,
                'processing_time_ms' => $processingTime,
            ],
        ]);
    }

    /**
     * POST /api/ai/analyze/turnover-risk
     * تحليل مخاطر التسرب الوظيفي
     */
    public function analyzeTurnoverRisk(): JsonResponse
    {
        $startTime = microtime(true);

        $log = AiAnalysisLog::create([
            'analysis_type' => 'turnover_prediction',
            'input_parameters' => ['scope' => 'all_active_employees'],
            'results' => [],
            'status' => 'processing',
            'triggered_by' => auth()->id(),
            'created_at' => now(),
        ]);

        $employees = Employee::where('status', 'active')
            ->with(['contracts' => fn($q) => $q->where('status', 'active')->latest()])
            ->withCount(['leaveRequests as total_leaves' => fn($q) => $q->where('start_date', '>=', now()->subYear())])
            ->get();

        $riskScores = [];

        foreach ($employees as $employee) {
            $factors = [];
            $score = 0;

            // عامل 1: مدة الخدمة القصيرة (أقل من سنة)
            if ($employee->hire_date && $employee->hire_date->diffInMonths(now()) < 12) {
                $factors['short_tenure'] = 0.2;
                $score += 0.2;
            }

            // عامل 2: كثرة الإجازات
            if ($employee->total_leaves > 10) {
                $factors['high_leave_frequency'] = 0.15;
                $score += 0.15;
            }

            // عامل 3: عقد قرب الانتهاء (أقل من 3 أشهر)
            $activeContract = $employee->contracts->first();
            if ($activeContract && $activeContract->end_date && $activeContract->end_date->diffInMonths(now()) < 3) {
                $factors['contract_expiring'] = 0.25;
                $score += 0.25;
            }

            // عامل 4: عدم وجود عقد نشط
            if (!$activeContract) {
                $factors['no_active_contract'] = 0.3;
                $score += 0.3;
            }

            // عامل 5: نوع التوظيف المؤقت
            if (in_array($employee->employment_type, ['temporary', 'tamheer'])) {
                $factors['temporary_employment'] = 0.15;
                $score += 0.15;
            }

            $score = min($score, 1.0);
            $riskLevel = match (true) {
                $score >= 0.7 => 'very_high',
                $score >= 0.5 => 'high',
                $score >= 0.3 => 'moderate',
                default => 'low',
            };

            // تحديث السجلات القديمة
            TurnoverRiskScore::where('employee_id', $employee->id)
                ->where('is_latest', true)
                ->update(['is_latest' => false]);

            $riskRecord = TurnoverRiskScore::create([
                'employee_id' => $employee->id,
                'analysis_log_id' => $log->id,
                'risk_score' => $score,
                'risk_level' => $riskLevel,
                'risk_factors' => $factors,
                'recommended_actions' => $this->getRetentionActions($riskLevel, $factors),
                'assessment_date' => now()->toDateString(),
                'valid_until' => now()->addMonths(3)->toDateString(),
                'is_latest' => true,
            ]);

            if ($score >= 0.5) {
                $riskScores[] = $riskRecord;
            }
        }

        $processingTime = (int) ((microtime(true) - $startTime) * 1000);

        $summary = [
            'total_analyzed' => $employees->count(),
            'low_risk' => TurnoverRiskScore::where('analysis_log_id', $log->id)->where('risk_level', 'low')->count(),
            'moderate_risk' => TurnoverRiskScore::where('analysis_log_id', $log->id)->where('risk_level', 'moderate')->count(),
            'high_risk' => TurnoverRiskScore::where('analysis_log_id', $log->id)->where('risk_level', 'high')->count(),
            'very_high_risk' => TurnoverRiskScore::where('analysis_log_id', $log->id)->where('risk_level', 'very_high')->count(),
        ];

        $log->update([
            'results' => $summary,
            'status' => 'completed',
            'data_points_analyzed' => $employees->count(),
            'processing_time_ms' => $processingTime,
            'confidence_score' => 0.78,
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحليل مخاطر التسرب الوظيفي',
            'data' => [
                'analysis_id' => $log->id,
                'summary' => $summary,
                'high_risk_employees' => collect($riskScores)->take(10)->load('employee:id,first_name,last_name,first_name_ar,last_name_ar,employee_number'),
                'processing_time_ms' => $processingTime,
            ],
        ]);
    }

    /**
     * GET /api/ai/predictions
     */
    public function predictions(Request $request): JsonResponse
    {
        $predictions = AiPrediction::with(['department:id,name,name_ar'])
            ->when($request->filled('type'), fn($q) => $q->where('prediction_type', $request->input('type')))
            ->when($request->filled('impact'), fn($q) => $q->where('impact_level', $request->input('impact')))
            ->when($request->boolean('active_only', true), fn($q) => $q->where('prediction_date', '>=', now()))
            ->orderByRaw("FIELD(impact_level, 'critical', 'high', 'medium', 'low')")
            ->orderBy('prediction_date')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $predictions,
        ]);
    }

    /**
     * POST /api/ai/predictions/{id}/acknowledge
     */
    public function acknowledgePrediction(string $id): JsonResponse
    {
        $prediction = AiPrediction::findOrFail($id);

        $prediction->update([
            'is_acknowledged' => true,
            'acknowledged_by' => auth()->id(),
            'acknowledged_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم الإحاطة بالتنبؤ',
            'data' => $prediction,
        ]);
    }

    /**
     * GET /api/ai/recommendations
     */
    public function recommendations(Request $request): JsonResponse
    {
        $recommendations = AiRecommendation::query()
            ->when($request->filled('type'), fn($q) => $q->where('recommendation_type', $request->input('type')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('priority'), fn($q) => $q->where('priority', $request->input('priority')))
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $recommendations,
        ]);
    }

    /**
     * PUT /api/ai/recommendations/{id}/review
     */
    public function reviewRecommendation(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:accepted,rejected,implemented',
            'review_notes' => 'nullable|string|max:1000',
        ]);

        $recommendation = AiRecommendation::findOrFail($id);

        $recommendation->update([
            'status' => $request->input('status'),
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $request->input('review_notes'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم مراجعة التوصية',
            'data' => $recommendation,
        ]);
    }

    /**
     * GET /api/ai/risk-scores
     * درجات مخاطر التسرب الوظيفي
     */
    public function riskScores(Request $request): JsonResponse
    {
        $scores = TurnoverRiskScore::with('employee:id,first_name,last_name,first_name_ar,last_name_ar,employee_number,department_id')
            ->where('is_latest', true)
            ->when($request->filled('risk_level'), fn($q) => $q->where('risk_level', $request->input('risk_level')))
            ->when($request->filled('department_id'), function ($q) use ($request) {
                $q->whereHas('employee', fn($eq) => $eq->where('department_id', $request->input('department_id')));
            })
            ->orderBy('risk_score', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $scores,
        ]);
    }

    /**
     * GET /api/ai/analysis-logs
     */
    public function analysisLogs(Request $request): JsonResponse
    {
        $logs = AiAnalysisLog::query()
            ->when($request->filled('type'), fn($q) => $q->where('analysis_type', $request->input('type')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    private function getRetentionActions(string $riskLevel, array $factors): array
    {
        $actions = [];

        if (isset($factors['short_tenure'])) {
            $actions[] = ['action' => 'mentorship', 'description_ar' => 'تعيين مرشد للموظف الجديد'];
        }
        if (isset($factors['high_leave_frequency'])) {
            $actions[] = ['action' => 'wellness_check', 'description_ar' => 'التحقق من رضا الموظف وظروف العمل'];
        }
        if (isset($factors['contract_expiring'])) {
            $actions[] = ['action' => 'renewal_discussion', 'description_ar' => 'مناقشة تجديد العقد مبكراً'];
        }
        if (isset($factors['no_active_contract'])) {
            $actions[] = ['action' => 'urgent_contract', 'description_ar' => 'إعداد عقد عمل عاجل'];
        }

        if ($riskLevel === 'very_high') {
            $actions[] = ['action' => 'retention_interview', 'description_ar' => 'مقابلة استبقاء عاجلة مع الإدارة'];
        }

        return $actions;
    }
}
