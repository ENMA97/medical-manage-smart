import { useState, useEffect, useCallback } from 'react';
import aiService from '../services/aiService';
import toast from 'react-hot-toast';

const riskColors = {
  low: 'bg-green-100 text-green-800',
  moderate: 'bg-yellow-100 text-yellow-800',
  high: 'bg-orange-100 text-orange-800',
  very_high: 'bg-red-100 text-red-800',
};

const priorityColors = {
  low: 'bg-gray-100 text-gray-800',
  medium: 'bg-blue-100 text-blue-800',
  high: 'bg-orange-100 text-orange-800',
  urgent: 'bg-red-100 text-red-800',
};

const priorityLabels = { low: 'منخفض', medium: 'متوسط', high: 'عالي', urgent: 'عاجل' };
const riskLabels = { low: 'منخفض', moderate: 'متوسط', high: 'عالي', very_high: 'عالي جداً' };

const statusLabels = {
  new: 'جديد', under_review: 'قيد المراجعة', accepted: 'مقبول',
  rejected: 'مرفوض', implemented: 'تم التنفيذ', expired: 'منتهي',
};

export default function AiInsights() {
  const [tab, setTab] = useState('dashboard');
  const [dashboard, setDashboard] = useState(null);
  const [recommendations, setRecommendations] = useState([]);
  const [riskScores, setRiskScores] = useState([]);
  const [loading, setLoading] = useState(true);
  const [analyzing, setAnalyzing] = useState(false);

  const fetchDashboard = useCallback(async () => {
    setLoading(true);
    try {
      const { data } = await aiService.getDashboard();
      setDashboard(data.data);
    } catch {
      // Dashboard might be empty initially
      setDashboard({ summary: { total_predictions: 0, unacknowledged_predictions: 0, pending_recommendations: 0, high_risk_count: 0 }, active_predictions: [], pending_recommendations: [], high_risk_employees: [], recent_analyses: [] });
    } finally {
      setLoading(false);
    }
  }, []);

  const fetchRecommendations = useCallback(async () => {
    try {
      const { data } = await aiService.getRecommendations();
      setRecommendations(data.data?.data || []);
    } catch { /* empty */ }
  }, []);

  const fetchRiskScores = useCallback(async () => {
    try {
      const { data } = await aiService.getRiskScores();
      setRiskScores(data.data?.data || []);
    } catch { /* empty */ }
  }, []);

  useEffect(() => {
    fetchDashboard();
  }, [fetchDashboard]);

  useEffect(() => {
    if (tab === 'recommendations') fetchRecommendations();
    if (tab === 'risk') fetchRiskScores();
  }, [tab, fetchRecommendations, fetchRiskScores]);

  async function runLeaveAnalysis() {
    setAnalyzing(true);
    try {
      await aiService.analyzeLeavePatterns();
      toast.success('تم تحليل أنماط الإجازات بنجاح');
      fetchDashboard();
      fetchRecommendations();
    } catch {
      toast.error('فشل في تحليل أنماط الإجازات');
    } finally {
      setAnalyzing(false);
    }
  }

  async function runTurnoverAnalysis() {
    setAnalyzing(true);
    try {
      await aiService.analyzeTurnoverRisk();
      toast.success('تم تحليل مخاطر التسرب الوظيفي');
      fetchDashboard();
      fetchRiskScores();
    } catch {
      toast.error('فشل في تحليل مخاطر التسرب');
    } finally {
      setAnalyzing(false);
    }
  }

  async function handleReview(id, status) {
    try {
      await aiService.reviewRecommendation(id, { status });
      toast.success('تم تحديث حالة التوصية');
      fetchRecommendations();
      fetchDashboard();
    } catch {
      toast.error('فشل في تحديث التوصية');
    }
  }

  const tabs = [
    { id: 'dashboard', label: 'نظرة عامة' },
    { id: 'recommendations', label: 'التوصيات' },
    { id: 'risk', label: 'مخاطر التسرب' },
  ];

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-800">الذكاء الاصطناعي والتحليلات</h1>
          <p className="text-sm text-gray-500 mt-1">تحليلات ذكية وتوصيات استباقية لإدارة الموارد البشرية</p>
        </div>
        <div className="flex gap-2">
          <button
            onClick={runLeaveAnalysis}
            disabled={analyzing}
            className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 text-sm font-medium"
          >
            {analyzing ? 'جاري التحليل...' : 'تحليل الإجازات'}
          </button>
          <button
            onClick={runTurnoverAnalysis}
            disabled={analyzing}
            className="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 text-sm font-medium"
          >
            {analyzing ? 'جاري التحليل...' : 'تحليل التسرب'}
          </button>
        </div>
      </div>

      {/* Tabs */}
      <div className="border-b border-gray-200">
        <div className="flex gap-4">
          {tabs.map((t) => (
            <button
              key={t.id}
              onClick={() => setTab(t.id)}
              className={`pb-3 px-1 text-sm font-medium border-b-2 transition-colors ${
                tab === t.id
                  ? 'border-blue-600 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700'
              }`}
            >
              {t.label}
            </button>
          ))}
        </div>
      </div>

      {/* Dashboard Tab */}
      {tab === 'dashboard' && dashboard && (
        <div className="space-y-6">
          {/* Summary Cards */}
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <SummaryCard title="التنبؤات النشطة" value={dashboard.summary.total_predictions} color="blue" />
            <SummaryCard title="تنبؤات غير مقروءة" value={dashboard.summary.unacknowledged_predictions} color="yellow" />
            <SummaryCard title="توصيات معلقة" value={dashboard.summary.pending_recommendations} color="purple" />
            <SummaryCard title="موظفون عالي المخاطر" value={dashboard.summary.high_risk_count} color="red" />
          </div>

          {/* High Risk Employees */}
          {dashboard.high_risk_employees?.length > 0 && (
            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
              <h3 className="text-lg font-semibold text-gray-800 mb-4">موظفون ذوو مخاطر عالية للتسرب</h3>
              <div className="space-y-3">
                {dashboard.high_risk_employees.map((r) => (
                  <div key={r.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                      <p className="font-medium text-gray-800">
                        {r.employee?.first_name_ar} {r.employee?.last_name_ar}
                      </p>
                      <p className="text-sm text-gray-500">{r.employee?.employee_number}</p>
                    </div>
                    <div className="flex items-center gap-3">
                      <span className="text-sm font-mono text-gray-600">{(r.risk_score * 100).toFixed(0)}%</span>
                      <span className={`text-xs px-2 py-1 rounded-full font-medium ${riskColors[r.risk_level]}`}>
                        {riskLabels[r.risk_level]}
                      </span>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Pending Recommendations */}
          {dashboard.pending_recommendations?.length > 0 && (
            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
              <h3 className="text-lg font-semibold text-gray-800 mb-4">أحدث التوصيات</h3>
              <div className="space-y-3">
                {dashboard.pending_recommendations.map((rec) => (
                  <div key={rec.id} className="p-4 bg-gray-50 rounded-lg">
                    <div className="flex items-start justify-between">
                      <div>
                        <p className="font-medium text-gray-800">{rec.title_ar || rec.title}</p>
                        <p className="text-sm text-gray-600 mt-1">{rec.description_ar || rec.description}</p>
                      </div>
                      <span className={`text-xs px-2 py-1 rounded-full font-medium whitespace-nowrap ${priorityColors[rec.priority]}`}>
                        {priorityLabels[rec.priority]}
                      </span>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Empty State */}
          {!dashboard.high_risk_employees?.length && !dashboard.pending_recommendations?.length && (
            <div className="text-center py-16 bg-white rounded-xl shadow-sm border border-gray-100">
              <svg className="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
              </svg>
              <h3 className="text-lg font-medium text-gray-600">لا توجد تحليلات بعد</h3>
              <p className="text-sm text-gray-400 mt-2">ابدأ بتشغيل تحليل الإجازات أو تحليل التسرب الوظيفي</p>
            </div>
          )}
        </div>
      )}

      {/* Recommendations Tab */}
      {tab === 'recommendations' && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-100">
          {recommendations.length === 0 ? (
            <div className="text-center py-12 text-gray-400">لا توجد توصيات — قم بتشغيل التحليل أولاً</div>
          ) : (
            <div className="divide-y divide-gray-100">
              {recommendations.map((rec) => (
                <div key={rec.id} className="p-5">
                  <div className="flex items-start justify-between gap-4">
                    <div className="flex-1">
                      <div className="flex items-center gap-2 mb-1">
                        <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${priorityColors[rec.priority]}`}>
                          {priorityLabels[rec.priority]}
                        </span>
                        <span className="text-xs text-gray-400">{statusLabels[rec.status]}</span>
                      </div>
                      <p className="font-medium text-gray-800">{rec.title_ar || rec.title}</p>
                      <p className="text-sm text-gray-600 mt-1">{rec.description_ar || rec.description}</p>
                    </div>
                    {(rec.status === 'new' || rec.status === 'under_review') && (
                      <div className="flex gap-2 shrink-0">
                        <button
                          onClick={() => handleReview(rec.id, 'accepted')}
                          className="px-3 py-1.5 text-sm bg-green-50 text-green-700 rounded-lg hover:bg-green-100"
                        >
                          قبول
                        </button>
                        <button
                          onClick={() => handleReview(rec.id, 'rejected')}
                          className="px-3 py-1.5 text-sm bg-red-50 text-red-700 rounded-lg hover:bg-red-100"
                        >
                          رفض
                        </button>
                      </div>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {/* Risk Scores Tab */}
      {tab === 'risk' && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-100">
          {riskScores.length === 0 ? (
            <div className="text-center py-12 text-gray-400">لا توجد تقييمات مخاطر — قم بتشغيل تحليل التسرب أولاً</div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead className="bg-gray-50 text-gray-600">
                  <tr>
                    <th className="text-right py-3 px-4 font-medium">الموظف</th>
                    <th className="text-right py-3 px-4 font-medium">الرقم الوظيفي</th>
                    <th className="text-center py-3 px-4 font-medium">درجة المخاطرة</th>
                    <th className="text-center py-3 px-4 font-medium">المستوى</th>
                    <th className="text-right py-3 px-4 font-medium">عوامل المخاطرة</th>
                    <th className="text-center py-3 px-4 font-medium">تاريخ التقييم</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {riskScores.map((score) => (
                    <tr key={score.id} className="hover:bg-gray-50">
                      <td className="py-3 px-4 font-medium text-gray-800">
                        {score.employee?.first_name_ar} {score.employee?.last_name_ar}
                      </td>
                      <td className="py-3 px-4 text-gray-500">{score.employee?.employee_number}</td>
                      <td className="py-3 px-4 text-center">
                        <div className="flex items-center justify-center gap-2">
                          <div className="w-16 bg-gray-200 rounded-full h-2">
                            <div
                              className={`h-2 rounded-full ${
                                score.risk_score >= 0.7 ? 'bg-red-500' :
                                score.risk_score >= 0.5 ? 'bg-orange-500' :
                                score.risk_score >= 0.3 ? 'bg-yellow-500' : 'bg-green-500'
                              }`}
                              style={{ width: `${score.risk_score * 100}%` }}
                            />
                          </div>
                          <span className="text-xs font-mono">{(score.risk_score * 100).toFixed(0)}%</span>
                        </div>
                      </td>
                      <td className="py-3 px-4 text-center">
                        <span className={`text-xs px-2 py-1 rounded-full font-medium ${riskColors[score.risk_level]}`}>
                          {riskLabels[score.risk_level]}
                        </span>
                      </td>
                      <td className="py-3 px-4">
                        <div className="flex flex-wrap gap-1">
                          {Object.keys(score.risk_factors || {}).map((factor) => (
                            <span key={factor} className="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">
                              {factorLabels[factor] || factor}
                            </span>
                          ))}
                        </div>
                      </td>
                      <td className="py-3 px-4 text-center text-gray-500 text-xs">
                        {score.assessment_date}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

const factorLabels = {
  short_tenure: 'خدمة قصيرة',
  high_leave_frequency: 'إجازات كثيرة',
  contract_expiring: 'عقد ينتهي قريباً',
  no_active_contract: 'بدون عقد',
  temporary_employment: 'توظيف مؤقت',
};

function SummaryCard({ title, value, color }) {
  const colors = {
    blue: 'bg-blue-50 text-blue-700 border-blue-100',
    yellow: 'bg-yellow-50 text-yellow-700 border-yellow-100',
    purple: 'bg-purple-50 text-purple-700 border-purple-100',
    red: 'bg-red-50 text-red-700 border-red-100',
  };

  return (
    <div className={`rounded-xl p-4 border ${colors[color]}`}>
      <p className="text-sm opacity-80">{title}</p>
      <p className="text-2xl font-bold mt-1">{value}</p>
    </div>
  );
}
