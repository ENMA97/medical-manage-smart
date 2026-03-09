import { useState, useEffect, useCallback } from 'react';
import toast from 'react-hot-toast';
import disciplinaryService from '../services/disciplinaryService';
import employeeService from '../services/employeeService';
import Modal from '../components/ui/Modal';

// ─── Labels & Colors ───
const statusLabels = {
  reported: 'مُبلّغ عنها', under_investigation: 'تحت التحقيق', decided: 'صدر قرار', appealed: 'تم التظلم', closed: 'مغلقة',
};
const statusColors = {
  reported: 'bg-yellow-100 text-yellow-700', under_investigation: 'bg-blue-100 text-blue-700',
  decided: 'bg-purple-100 text-purple-700', appealed: 'bg-orange-100 text-orange-700', closed: 'bg-gray-100 text-gray-600',
};
const severityLabels = { minor: 'بسيطة', moderate: 'متوسطة', major: 'جسيمة', critical: 'خطيرة' };
const severityColors = {
  minor: 'bg-green-100 text-green-700', moderate: 'bg-yellow-100 text-yellow-700',
  major: 'bg-orange-100 text-orange-700', critical: 'bg-red-100 text-red-700',
};
const decisionStatusLabels = { draft: 'مسودة', issued: 'صادر', notified: 'مُبلّغ', acknowledged: 'اطلع الموظف', appealed: 'تم التظلم', final: 'نهائي' };
const decisionStatusColors = {
  draft: 'bg-gray-100 text-gray-600', issued: 'bg-blue-100 text-blue-700', notified: 'bg-yellow-100 text-yellow-700',
  acknowledged: 'bg-purple-100 text-purple-700', appealed: 'bg-orange-100 text-orange-700', final: 'bg-green-100 text-green-700',
};

// ─── Icons (SVG) ───
const Icons = {
  violation: (
    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
    </svg>
  ),
  investigation: (
    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
    </svg>
  ),
  decision: (
    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
    </svg>
  ),
  committee: (
    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
    </svg>
  ),
  gavel: (
    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
    </svg>
  ),
  suggest: (
    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
    </svg>
  ),
};

// ─── Tab component ───
function TabButton({ active, icon, label, count, onClick }) {
  return (
    <button
      onClick={onClick}
      className={`flex items-center gap-2 px-4 py-3 rounded-lg font-medium transition-all text-sm ${
        active ? 'bg-indigo-600 text-white shadow-md' : 'bg-white text-gray-600 hover:bg-gray-50 border'
      }`}
    >
      {icon}
      <span>{label}</span>
      {count > 0 && (
        <span className={`px-2 py-0.5 rounded-full text-xs ${active ? 'bg-white/20' : 'bg-indigo-100 text-indigo-700'}`}>
          {count}
        </span>
      )}
    </button>
  );
}

export default function Disciplinary() {
  const [activeTab, setActiveTab] = useState('violations');
  const [violations, setViolations] = useState([]);
  const [decisions, setDecisions] = useState([]);
  const [violationTypes, setViolationTypes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [meta, setMeta] = useState({});
  const [employees, setEmployees] = useState([]);

  // Modals
  const [showCreate, setShowCreate] = useState(false);
  const [showDetail, setShowDetail] = useState(null);
  const [showCommittee, setShowCommittee] = useState(null);
  const [showDecision, setShowDecision] = useState(null);
  const [suggestedPenalty, setSuggestedPenalty] = useState(null);
  const [saving, setSaving] = useState(false);

  // Forms
  const [violationForm, setViolationForm] = useState({
    employee_id: '', violation_type_id: '', violation_date: '', violation_time: '',
    location: '', description: '', description_ar: '',
  });

  const [committeeForm, setCommitteeForm] = useState({
    name: '', name_ar: '', chairman_id: '', deadline: '', mandate_ar: '',
    members: [{ employee_id: '', role: 'member', role_ar: 'عضو' }],
  });

  const [decisionForm, setDecisionForm] = useState({
    penalty_type: '', penalty_type_ar: '', penalty_details_ar: '',
    deduction_days: '', suspension_days: '', effective_date: '',
    justification: '', justification_ar: '', notes: '',
  });

  // ─── Data Loading ───
  const fetchViolations = useCallback(async () => {
    setLoading(true);
    try {
      const { data } = await disciplinaryService.getViolations({ search, page, per_page: 15 });
      setViolations(data.data?.data || data.data || []);
      setMeta(data.data?.meta || data.meta || {});
    } catch { toast.error('خطأ في تحميل المخالفات'); }
    finally { setLoading(false); }
  }, [search, page]);

  const fetchDecisions = useCallback(async () => {
    setLoading(true);
    try {
      const { data } = await disciplinaryService.getDecisions({ page, per_page: 15 });
      setDecisions(data.data?.data || data.data || []);
    } catch { toast.error('خطأ في تحميل القرارات'); }
    finally { setLoading(false); }
  }, [page]);

  useEffect(() => {
    if (activeTab === 'violations') fetchViolations();
    else if (activeTab === 'decisions') fetchDecisions();
  }, [activeTab, fetchViolations, fetchDecisions]);

  useEffect(() => { setPage(1); }, [search]);

  async function loadViolationTypes() {
    if (violationTypes.length > 0) return;
    try {
      const { data } = await disciplinaryService.getViolationTypes();
      setViolationTypes(data.data || []);
    } catch { /* silent */ }
  }

  async function loadEmployees() {
    if (employees.length > 0) return;
    try {
      const { data } = await employeeService.getAll({ per_page: 200 });
      setEmployees(data.data?.data || data.data || []);
    } catch { /* silent */ }
  }

  // ─── Suggest Penalty ───
  async function fetchSuggestedPenalty(typeId, employeeId) {
    if (!typeId) { setSuggestedPenalty(null); return; }
    try {
      const { data } = await disciplinaryService.suggestPenalty(typeId, { employee_id: employeeId });
      setSuggestedPenalty(data.data);
    } catch { setSuggestedPenalty(null); }
  }

  // ─── Create Violation ───
  function openCreateViolation() {
    loadEmployees();
    loadViolationTypes();
    setViolationForm({ employee_id: '', violation_type_id: '', violation_date: '', violation_time: '', location: '', description: '', description_ar: '' });
    setSuggestedPenalty(null);
    setShowCreate(true);
  }

  async function handleCreateViolation(e) {
    e.preventDefault();
    setSaving(true);
    try {
      const { data } = await disciplinaryService.createViolation(violationForm);
      toast.success(data.message || 'تم تسجيل المخالفة');
      setShowCreate(false);
      fetchViolations();
    } catch (err) {
      toast.error(err.response?.data?.message || 'خطأ في تسجيل المخالفة');
    } finally { setSaving(false); }
  }

  // ─── View Detail ───
  async function openDetail(id) {
    try {
      const { data } = await disciplinaryService.getViolation(id);
      setShowDetail(data.data);
      setSuggestedPenalty(data.suggested_penalty);
    } catch { toast.error('خطأ في تحميل التفاصيل'); }
  }

  // ─── Form Committee ───
  function openCommitteeForm(violation) {
    loadEmployees();
    setCommitteeForm({
      name: `Investigation Committee - ${violation.violation_number}`,
      name_ar: `لجنة تحقيق - ${violation.violation_number}`,
      chairman_id: '', deadline: '', mandate_ar: 'التحقيق في المخالفة المسجلة وتقديم التوصيات',
      members: [{ employee_id: '', role: 'member', role_ar: 'عضو' }],
    });
    setShowCommittee(violation);
  }

  async function handleFormCommittee(e) {
    e.preventDefault();
    setSaving(true);
    try {
      const payload = { ...committeeForm, members: committeeForm.members.filter(m => m.employee_id) };
      const { data } = await disciplinaryService.formCommittee(showCommittee.id, payload);
      toast.success(data.message || 'تم تشكيل اللجنة');
      setShowCommittee(null);
      fetchViolations();
    } catch (err) {
      toast.error(err.response?.data?.message || 'خطأ في تشكيل اللجنة');
    } finally { setSaving(false); }
  }

  function addMember() {
    setCommitteeForm(prev => ({
      ...prev,
      members: [...prev.members, { employee_id: '', role: 'member', role_ar: 'عضو' }],
    }));
  }

  function removeMember(index) {
    setCommitteeForm(prev => ({
      ...prev,
      members: prev.members.filter((_, i) => i !== index),
    }));
  }

  // ─── Issue Decision ───
  function openDecisionForm(violation) {
    const sp = suggestedPenalty;
    setDecisionForm({
      penalty_type: sp?.penalty || '', penalty_type_ar: sp?.penalty_ar || '',
      penalty_details_ar: sp?.details_ar || '', deduction_days: sp?.deduction_days || '',
      suspension_days: '', effective_date: new Date().toISOString().split('T')[0],
      justification: '', justification_ar: '', notes: '',
    });
    setShowDecision(violation);
  }

  async function handleIssueDecision(e) {
    e.preventDefault();
    setSaving(true);
    try {
      const { data } = await disciplinaryService.issueDecision(showDecision.id, decisionForm);
      toast.success(data.message || 'تم إصدار القرار');
      setShowDecision(null);
      setShowDetail(null);
      fetchViolations();
    } catch (err) {
      toast.error(err.response?.data?.message || 'خطأ في إصدار القرار');
    } finally { setSaving(false); }
  }

  // ─── Approve Decision ───
  async function handleApproveDecision(id) {
    try {
      const { data } = await disciplinaryService.approveDecision(id);
      toast.success(data.message || 'تم اعتماد القرار');
      fetchDecisions();
    } catch (err) {
      toast.error(err.response?.data?.message || 'خطأ في اعتماد القرار');
    }
  }

  // ─── Render ───
  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
            {Icons.gavel}
            <span>المساءلات والتحقيق</span>
          </h1>
          <p className="text-gray-500 text-sm mt-1">إدارة المخالفات ولجان التحقيق وإصدار القرارات التأديبية</p>
        </div>
        <button onClick={openCreateViolation}
          className="flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2.5 rounded-lg transition shadow-sm">
          {Icons.violation}
          <span>تسجيل مخالفة</span>
        </button>
      </div>

      {/* Tabs */}
      <div className="flex gap-2 flex-wrap">
        <TabButton active={activeTab === 'violations'} icon={Icons.violation} label="المخالفات" count={violations.length} onClick={() => setActiveTab('violations')} />
        <TabButton active={activeTab === 'decisions'} icon={Icons.decision} label="القرارات" count={decisions.length} onClick={() => setActiveTab('decisions')} />
        <TabButton active={activeTab === 'types'} icon={Icons.suggest} label="جدول العقوبات" count={violationTypes.length} onClick={() => { setActiveTab('types'); loadViolationTypes(); }} />
      </div>

      {/* Search */}
      {activeTab !== 'types' && (
        <div className="relative max-w-md">
          <input type="text" value={search} onChange={(e) => setSearch(e.target.value)}
            placeholder="بحث..." className="w-full border rounded-lg px-4 py-2.5 pr-10 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
          <svg className="absolute right-3 top-3 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
      )}

      {/* ═══ TAB: Violations ═══ */}
      {activeTab === 'violations' && (
        <div className="bg-white rounded-xl shadow-sm border overflow-hidden">
          {loading ? (
            <div className="p-12 text-center text-gray-500">جاري التحميل...</div>
          ) : violations.length === 0 ? (
            <div className="p-12 text-center text-gray-400">لا توجد مخالفات مسجلة</div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead className="bg-gray-50 text-gray-600">
                  <tr>
                    <th className="px-4 py-3 text-right">رقم المخالفة</th>
                    <th className="px-4 py-3 text-right">الموظف</th>
                    <th className="px-4 py-3 text-right">نوع المخالفة</th>
                    <th className="px-4 py-3 text-right">الخطورة</th>
                    <th className="px-4 py-3 text-right">التكرار</th>
                    <th className="px-4 py-3 text-right">الحالة</th>
                    <th className="px-4 py-3 text-right">التاريخ</th>
                    <th className="px-4 py-3 text-right">إجراءات</th>
                  </tr>
                </thead>
                <tbody className="divide-y">
                  {violations.map(v => (
                    <tr key={v.id} className="hover:bg-gray-50">
                      <td className="px-4 py-3 font-mono text-xs">{v.violation_number}</td>
                      <td className="px-4 py-3">
                        <div className="font-medium">{v.employee?.first_name_ar} {v.employee?.last_name_ar}</div>
                        <div className="text-xs text-gray-500">{v.employee?.employee_number}</div>
                      </td>
                      <td className="px-4 py-3">{v.violation_type?.name_ar}</td>
                      <td className="px-4 py-3">
                        <span className={`px-2 py-1 rounded-full text-xs ${severityColors[v.violation_type?.severity] || 'bg-gray-100'}`}>
                          {severityLabels[v.violation_type?.severity] || v.violation_type?.severity}
                        </span>
                      </td>
                      <td className="px-4 py-3 text-center">
                        <span className="bg-gray-100 text-gray-700 px-2 py-1 rounded-full text-xs font-bold">
                          {v.occurrence_number}
                        </span>
                      </td>
                      <td className="px-4 py-3">
                        <span className={`px-2 py-1 rounded-full text-xs ${statusColors[v.status] || 'bg-gray-100'}`}>
                          {statusLabels[v.status] || v.status}
                        </span>
                      </td>
                      <td className="px-4 py-3 text-xs">{v.violation_date}</td>
                      <td className="px-4 py-3">
                        <div className="flex gap-1">
                          <button onClick={() => openDetail(v.id)} title="التفاصيل"
                            className="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition">
                            {Icons.investigation}
                          </button>
                          {v.status === 'reported' && v.violation_type?.requires_investigation && (
                            <button onClick={() => { openDetail(v.id); setTimeout(() => openCommitteeForm(v), 300); }} title="تشكيل لجنة"
                              className="p-1.5 text-purple-600 hover:bg-purple-50 rounded-lg transition">
                              {Icons.committee}
                            </button>
                          )}
                          {(v.status === 'reported' || v.status === 'under_investigation') && (
                            <button onClick={() => { openDetail(v.id); }} title="إصدار قرار"
                              className="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition">
                              {Icons.decision}
                            </button>
                          )}
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}

      {/* ═══ TAB: Decisions ═══ */}
      {activeTab === 'decisions' && (
        <div className="bg-white rounded-xl shadow-sm border overflow-hidden">
          {loading ? (
            <div className="p-12 text-center text-gray-500">جاري التحميل...</div>
          ) : decisions.length === 0 ? (
            <div className="p-12 text-center text-gray-400">لا توجد قرارات تأديبية</div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead className="bg-gray-50 text-gray-600">
                  <tr>
                    <th className="px-4 py-3 text-right">رقم القرار</th>
                    <th className="px-4 py-3 text-right">الموظف</th>
                    <th className="px-4 py-3 text-right">العقوبة</th>
                    <th className="px-4 py-3 text-right">المرجع القانوني</th>
                    <th className="px-4 py-3 text-right">الحالة</th>
                    <th className="px-4 py-3 text-right">إجراءات</th>
                  </tr>
                </thead>
                <tbody className="divide-y">
                  {decisions.map(d => (
                    <tr key={d.id} className="hover:bg-gray-50">
                      <td className="px-4 py-3 font-mono text-xs">{d.decision_number}</td>
                      <td className="px-4 py-3">{d.employee?.first_name_ar} {d.employee?.last_name_ar}</td>
                      <td className="px-4 py-3">
                        <div className="font-medium">{d.penalty_type_ar}</div>
                        {d.deduction_days > 0 && <div className="text-xs text-red-500">خصم {d.deduction_days} يوم</div>}
                      </td>
                      <td className="px-4 py-3 text-xs text-gray-500">{d.labor_law_reference}</td>
                      <td className="px-4 py-3">
                        <span className={`px-2 py-1 rounded-full text-xs ${decisionStatusColors[d.status] || 'bg-gray-100'}`}>
                          {decisionStatusLabels[d.status] || d.status}
                        </span>
                      </td>
                      <td className="px-4 py-3">
                        {d.status === 'issued' && (
                          <button onClick={() => handleApproveDecision(d.id)}
                            className="text-xs bg-green-600 text-white px-3 py-1 rounded-lg hover:bg-green-700 transition">
                            اعتماد
                          </button>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}

      {/* ═══ TAB: Violation Types (Labor Law Reference) ═══ */}
      {activeTab === 'types' && (
        <div className="space-y-4">
          {violationTypes.length === 0 ? (
            <div className="bg-white rounded-xl shadow-sm border p-12 text-center text-gray-400">جاري التحميل...</div>
          ) : (
            Object.entries(
              violationTypes.reduce((acc, t) => {
                const cat = t.category_ar || t.category;
                if (!acc[cat]) acc[cat] = [];
                acc[cat].push(t);
                return acc;
              }, {})
            ).map(([category, types]) => (
              <div key={category} className="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div className="bg-indigo-50 px-4 py-3 border-b flex items-center gap-2">
                  {Icons.gavel}
                  <h3 className="font-bold text-indigo-800">{category}</h3>
                </div>
                <div className="divide-y">
                  {types.map(type => (
                    <div key={type.id} className="p-4">
                      <div className="flex items-start justify-between gap-4 mb-3">
                        <div className="flex-1">
                          <div className="flex items-center gap-2 mb-1">
                            <span className="font-mono text-xs bg-gray-100 px-2 py-0.5 rounded">{type.code}</span>
                            <h4 className="font-bold text-gray-800">{type.name_ar}</h4>
                            <span className={`px-2 py-0.5 rounded-full text-xs ${severityColors[type.severity]}`}>
                              {severityLabels[type.severity]}
                            </span>
                            {type.requires_investigation && (
                              <span className="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-700 flex items-center gap-1">
                                {Icons.investigation}
                                يتطلب تحقيق
                              </span>
                            )}
                          </div>
                          <p className="text-sm text-gray-500">{type.description_ar}</p>
                          {type.labor_law_article && (
                            <p className="text-xs text-indigo-600 mt-1 flex items-center gap-1">
                              <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                              {type.labor_law_article}
                            </p>
                          )}
                        </div>
                      </div>
                      {/* Penalties progression */}
                      {type.penalties && type.penalties.length > 0 && (
                        <div className="mt-2">
                          <p className="text-xs font-medium text-gray-500 mb-2">العقوبات التدريجية:</p>
                          <div className="flex flex-wrap gap-2">
                            {type.penalties.map((p, idx) => (
                              <div key={idx} className="flex items-center gap-1 bg-gray-50 border rounded-lg px-3 py-1.5 text-xs">
                                <span className="font-bold text-indigo-600">المرة {p.occurrence}:</span>
                                <span className="text-gray-700">{p.penalty_ar}</span>
                                {p.deduction_days && <span className="text-red-500 font-medium">(خصم {p.deduction_days} يوم)</span>}
                              </div>
                            ))}
                          </div>
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              </div>
            ))
          )}
        </div>
      )}

      {/* ═══ MODAL: Create Violation ═══ */}
      <Modal isOpen={showCreate} onClose={() => setShowCreate(false)} title="تسجيل مخالفة جديدة" size="lg">
        <form onSubmit={handleCreateViolation} className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">الموظف *</label>
              <select value={violationForm.employee_id}
                onChange={(e) => {
                  setViolationForm(f => ({ ...f, employee_id: e.target.value }));
                  if (violationForm.violation_type_id) fetchSuggestedPenalty(violationForm.violation_type_id, e.target.value);
                }}
                className="w-full border rounded-lg px-3 py-2" required>
                <option value="">اختر الموظف</option>
                {employees.map(emp => (
                  <option key={emp.id} value={emp.id}>{emp.first_name_ar} {emp.last_name_ar} - {emp.employee_number}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">نوع المخالفة *</label>
              <select value={violationForm.violation_type_id}
                onChange={(e) => {
                  setViolationForm(f => ({ ...f, violation_type_id: e.target.value }));
                  fetchSuggestedPenalty(e.target.value, violationForm.employee_id);
                }}
                className="w-full border rounded-lg px-3 py-2" required>
                <option value="">اختر نوع المخالفة</option>
                {violationTypes.map(t => (
                  <option key={t.id} value={t.id}>{t.code} - {t.name_ar}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">تاريخ المخالفة *</label>
              <input type="date" value={violationForm.violation_date}
                onChange={(e) => setViolationForm(f => ({ ...f, violation_date: e.target.value }))}
                className="w-full border rounded-lg px-3 py-2" required />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">الوقت</label>
              <input type="time" value={violationForm.violation_time}
                onChange={(e) => setViolationForm(f => ({ ...f, violation_time: e.target.value }))}
                className="w-full border rounded-lg px-3 py-2" />
            </div>
            <div className="md:col-span-2">
              <label className="block text-sm font-medium text-gray-700 mb-1">المكان</label>
              <input type="text" value={violationForm.location}
                onChange={(e) => setViolationForm(f => ({ ...f, location: e.target.value }))}
                className="w-full border rounded-lg px-3 py-2" placeholder="مكان وقوع المخالفة" />
            </div>
            <div className="md:col-span-2">
              <label className="block text-sm font-medium text-gray-700 mb-1">وصف المخالفة *</label>
              <textarea value={violationForm.description_ar || violationForm.description}
                onChange={(e) => setViolationForm(f => ({ ...f, description: e.target.value, description_ar: e.target.value }))}
                className="w-full border rounded-lg px-3 py-2" rows={3} placeholder="وصف تفصيلي للمخالفة" required />
            </div>
          </div>

          {/* Suggested Penalty Box */}
          {suggestedPenalty && (
            <div className="bg-amber-50 border border-amber-200 rounded-lg p-4">
              <div className="flex items-center gap-2 mb-2">
                {Icons.suggest}
                <h4 className="font-bold text-amber-800">العقوبة المقترحة تلقائياً</h4>
              </div>
              <div className="grid grid-cols-2 gap-3 text-sm">
                <div>
                  <span className="text-gray-500">رقم التكرار:</span>
                  <span className="font-bold text-amber-700 mr-2">المرة {suggestedPenalty.occurrence_number}</span>
                </div>
                <div>
                  <span className="text-gray-500">الخطورة:</span>
                  <span className={`mr-2 px-2 py-0.5 rounded-full text-xs ${severityColors[suggestedPenalty.severity]}`}>
                    {severityLabels[suggestedPenalty.severity]}
                  </span>
                </div>
                {suggestedPenalty.suggested_penalty && (
                  <>
                    <div className="col-span-2">
                      <span className="text-gray-500">العقوبة:</span>
                      <span className="font-bold text-red-700 mr-2">{suggestedPenalty.suggested_penalty.penalty_ar}</span>
                    </div>
                    {suggestedPenalty.suggested_penalty.details_ar && (
                      <div className="col-span-2">
                        <span className="text-gray-500">التفاصيل:</span>
                        <span className="text-gray-700 mr-2">{suggestedPenalty.suggested_penalty.details_ar}</span>
                      </div>
                    )}
                  </>
                )}
                {suggestedPenalty.labor_law_article && (
                  <div className="col-span-2">
                    <span className="text-gray-500">المرجع القانوني:</span>
                    <span className="text-indigo-600 mr-2">{suggestedPenalty.labor_law_article}</span>
                  </div>
                )}
                {suggestedPenalty.requires_investigation && (
                  <div className="col-span-2 flex items-center gap-1 text-blue-600">
                    {Icons.investigation}
                    <span className="text-sm font-medium">هذه المخالفة تتطلب تشكيل لجنة تحقيق</span>
                  </div>
                )}
              </div>
            </div>
          )}

          <div className="flex justify-end gap-3 pt-4 border-t">
            <button type="button" onClick={() => setShowCreate(false)} className="px-4 py-2 border rounded-lg hover:bg-gray-50">إلغاء</button>
            <button type="submit" disabled={saving} className="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50">
              {saving ? 'جاري الحفظ...' : 'تسجيل المخالفة'}
            </button>
          </div>
        </form>
      </Modal>

      {/* ═══ MODAL: Violation Detail ═══ */}
      <Modal isOpen={!!showDetail} onClose={() => setShowDetail(null)} title="تفاصيل المخالفة" size="lg">
        {showDetail && (
          <div className="space-y-4">
            {/* Info Grid */}
            <div className="grid grid-cols-2 gap-4 text-sm">
              <div className="bg-gray-50 rounded-lg p-3">
                <span className="text-gray-500 block">رقم المخالفة</span>
                <span className="font-bold">{showDetail.violation_number}</span>
              </div>
              <div className="bg-gray-50 rounded-lg p-3">
                <span className="text-gray-500 block">الموظف</span>
                <span className="font-bold">{showDetail.employee?.first_name_ar} {showDetail.employee?.last_name_ar}</span>
              </div>
              <div className="bg-gray-50 rounded-lg p-3">
                <span className="text-gray-500 block">نوع المخالفة</span>
                <span className="font-bold">{showDetail.violation_type?.name_ar}</span>
              </div>
              <div className="bg-gray-50 rounded-lg p-3">
                <span className="text-gray-500 block">التكرار / الحالة</span>
                <span className="font-bold">المرة {showDetail.occurrence_number} - {statusLabels[showDetail.status]}</span>
              </div>
              <div className="bg-gray-50 rounded-lg p-3 col-span-2">
                <span className="text-gray-500 block">الوصف</span>
                <span>{showDetail.description_ar || showDetail.description}</span>
              </div>
            </div>

            {/* Suggested Penalty */}
            {suggestedPenalty && (
              <div className="bg-amber-50 border border-amber-200 rounded-lg p-3">
                <div className="flex items-center gap-2 mb-1">
                  {Icons.suggest}
                  <span className="font-bold text-amber-800 text-sm">العقوبة المقترحة:</span>
                  <span className="font-bold text-red-700">{suggestedPenalty?.penalty_ar || 'غير محدد'}</span>
                </div>
                {suggestedPenalty?.details_ar && <p className="text-xs text-gray-600 mr-7">{suggestedPenalty.details_ar}</p>}
              </div>
            )}

            {/* Committee info */}
            {showDetail.committee && (
              <div className="bg-blue-50 border border-blue-200 rounded-lg p-3">
                <div className="flex items-center gap-2 mb-2">
                  {Icons.committee}
                  <span className="font-bold text-blue-800">لجنة التحقيق: {showDetail.committee.name_ar}</span>
                </div>
                <div className="flex flex-wrap gap-2">
                  {showDetail.committee.members?.map(m => (
                    <span key={m.id} className="bg-white border px-2 py-1 rounded text-xs">
                      {m.employee?.first_name_ar} {m.employee?.last_name_ar} ({m.role_ar || m.role})
                    </span>
                  ))}
                </div>
              </div>
            )}

            {/* Decisions */}
            {showDetail.decisions?.length > 0 && (
              <div className="bg-purple-50 border border-purple-200 rounded-lg p-3">
                <div className="flex items-center gap-2 mb-2">
                  {Icons.decision}
                  <span className="font-bold text-purple-800">القرارات الصادرة</span>
                </div>
                {showDetail.decisions.map(d => (
                  <div key={d.id} className="bg-white rounded p-2 mb-1 text-sm">
                    <span className="font-mono text-xs">{d.decision_number}</span> - <span className="font-bold">{d.penalty_type_ar}</span>
                    {d.deduction_days > 0 && <span className="text-red-500 mr-1">(خصم {d.deduction_days} يوم)</span>}
                  </div>
                ))}
              </div>
            )}

            {/* Action Buttons */}
            <div className="flex gap-3 pt-3 border-t">
              {(showDetail.status === 'reported' && showDetail.violation_type?.requires_investigation && !showDetail.committee) && (
                <button onClick={() => { setShowDetail(null); openCommitteeForm(showDetail); }}
                  className="flex items-center gap-2 bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition">
                  {Icons.committee}
                  تشكيل لجنة تحقيق
                </button>
              )}
              {(showDetail.status === 'reported' || showDetail.status === 'under_investigation') && (
                <button onClick={() => { setShowDetail(null); openDecisionForm(showDetail); }}
                  className="flex items-center gap-2 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                  {Icons.decision}
                  إصدار قرار
                </button>
              )}
              <button onClick={() => setShowDetail(null)}
                className="px-4 py-2 border rounded-lg hover:bg-gray-50 mr-auto">إغلاق</button>
            </div>
          </div>
        )}
      </Modal>

      {/* ═══ MODAL: Form Committee ═══ */}
      <Modal isOpen={!!showCommittee} onClose={() => setShowCommittee(null)} title="تشكيل لجنة تحقيق" size="lg">
        <form onSubmit={handleFormCommittee} className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="md:col-span-2">
              <label className="block text-sm font-medium text-gray-700 mb-1">اسم اللجنة *</label>
              <input type="text" value={committeeForm.name_ar}
                onChange={(e) => setCommitteeForm(f => ({ ...f, name_ar: e.target.value, name: e.target.value }))}
                className="w-full border rounded-lg px-3 py-2" required />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">رئيس اللجنة *</label>
              <select value={committeeForm.chairman_id}
                onChange={(e) => setCommitteeForm(f => ({ ...f, chairman_id: e.target.value }))}
                className="w-full border rounded-lg px-3 py-2" required>
                <option value="">اختر رئيس اللجنة</option>
                {employees.map(emp => (
                  <option key={emp.id} value={emp.id}>{emp.first_name_ar} {emp.last_name_ar}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">الموعد النهائي</label>
              <input type="date" value={committeeForm.deadline}
                onChange={(e) => setCommitteeForm(f => ({ ...f, deadline: e.target.value }))}
                className="w-full border rounded-lg px-3 py-2" />
            </div>
            <div className="md:col-span-2">
              <label className="block text-sm font-medium text-gray-700 mb-1">صلاحيات اللجنة</label>
              <textarea value={committeeForm.mandate_ar}
                onChange={(e) => setCommitteeForm(f => ({ ...f, mandate_ar: e.target.value, mandate: e.target.value }))}
                className="w-full border rounded-lg px-3 py-2" rows={2} />
            </div>
          </div>

          {/* Members */}
          <div>
            <div className="flex items-center justify-between mb-2">
              <label className="text-sm font-medium text-gray-700">أعضاء اللجنة *</label>
              <button type="button" onClick={addMember} className="text-xs text-indigo-600 hover:text-indigo-800">+ إضافة عضو</button>
            </div>
            {committeeForm.members.map((m, idx) => (
              <div key={idx} className="flex gap-2 mb-2">
                <select value={m.employee_id}
                  onChange={(e) => {
                    const members = [...committeeForm.members];
                    members[idx].employee_id = e.target.value;
                    setCommitteeForm(f => ({ ...f, members }));
                  }}
                  className="flex-1 border rounded-lg px-3 py-2 text-sm" required>
                  <option value="">اختر عضو</option>
                  {employees.map(emp => (
                    <option key={emp.id} value={emp.id}>{emp.first_name_ar} {emp.last_name_ar}</option>
                  ))}
                </select>
                <select value={m.role}
                  onChange={(e) => {
                    const members = [...committeeForm.members];
                    const roleLabels = { chairman: 'رئيس', member: 'عضو', secretary: 'مقرر', observer: 'مراقب' };
                    members[idx].role = e.target.value;
                    members[idx].role_ar = roleLabels[e.target.value];
                    setCommitteeForm(f => ({ ...f, members }));
                  }}
                  className="w-32 border rounded-lg px-3 py-2 text-sm">
                  <option value="member">عضو</option>
                  <option value="chairman">رئيس</option>
                  <option value="secretary">مقرر</option>
                  <option value="observer">مراقب</option>
                </select>
                {committeeForm.members.length > 1 && (
                  <button type="button" onClick={() => removeMember(idx)} className="text-red-500 hover:text-red-700 px-2">X</button>
                )}
              </div>
            ))}
          </div>

          <div className="flex justify-end gap-3 pt-4 border-t">
            <button type="button" onClick={() => setShowCommittee(null)} className="px-4 py-2 border rounded-lg hover:bg-gray-50">إلغاء</button>
            <button type="submit" disabled={saving} className="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50">
              {saving ? 'جاري التشكيل...' : 'تشكيل اللجنة'}
            </button>
          </div>
        </form>
      </Modal>

      {/* ═══ MODAL: Issue Decision ═══ */}
      <Modal isOpen={!!showDecision} onClose={() => setShowDecision(null)} title="إصدار قرار تأديبي" size="lg">
        <form onSubmit={handleIssueDecision} className="space-y-4">
          {/* Auto-suggested penalty banner */}
          {suggestedPenalty?.penalty_ar && (
            <div className="bg-amber-50 border border-amber-200 rounded-lg p-3 flex items-center gap-2">
              {Icons.suggest}
              <div>
                <span className="text-sm text-amber-800 font-bold">مقترح تلقائي: </span>
                <span className="text-sm text-red-700 font-bold">{suggestedPenalty.penalty_ar}</span>
                {suggestedPenalty.details_ar && <p className="text-xs text-gray-600">{suggestedPenalty.details_ar}</p>}
              </div>
              <button type="button" onClick={() => {
                setDecisionForm(f => ({
                  ...f,
                  penalty_type: suggestedPenalty.penalty || '',
                  penalty_type_ar: suggestedPenalty.penalty_ar || '',
                  penalty_details_ar: suggestedPenalty.details_ar || '',
                  deduction_days: suggestedPenalty.deduction_days || '',
                }));
              }} className="mr-auto text-xs bg-amber-600 text-white px-3 py-1 rounded-lg hover:bg-amber-700">
                تطبيق المقترح
              </button>
            </div>
          )}

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">نوع العقوبة *</label>
              <input type="text" value={decisionForm.penalty_type_ar}
                onChange={(e) => setDecisionForm(f => ({ ...f, penalty_type_ar: e.target.value, penalty_type: e.target.value }))}
                className="w-full border rounded-lg px-3 py-2" required placeholder="مثال: إنذار كتابي" />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">تاريخ السريان *</label>
              <input type="date" value={decisionForm.effective_date}
                onChange={(e) => setDecisionForm(f => ({ ...f, effective_date: e.target.value }))}
                className="w-full border rounded-lg px-3 py-2" required />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">أيام الخصم</label>
              <input type="number" value={decisionForm.deduction_days}
                onChange={(e) => setDecisionForm(f => ({ ...f, deduction_days: e.target.value }))}
                className="w-full border rounded-lg px-3 py-2" min="0" placeholder="0" />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">أيام الإيقاف</label>
              <input type="number" value={decisionForm.suspension_days}
                onChange={(e) => setDecisionForm(f => ({ ...f, suspension_days: e.target.value }))}
                className="w-full border rounded-lg px-3 py-2" min="0" placeholder="0" />
            </div>
            <div className="md:col-span-2">
              <label className="block text-sm font-medium text-gray-700 mb-1">تفاصيل العقوبة</label>
              <textarea value={decisionForm.penalty_details_ar}
                onChange={(e) => setDecisionForm(f => ({ ...f, penalty_details_ar: e.target.value }))}
                className="w-full border rounded-lg px-3 py-2" rows={2} />
            </div>
            <div className="md:col-span-2">
              <label className="block text-sm font-medium text-gray-700 mb-1">المبررات *</label>
              <textarea value={decisionForm.justification_ar || decisionForm.justification}
                onChange={(e) => setDecisionForm(f => ({ ...f, justification: e.target.value, justification_ar: e.target.value }))}
                className="w-full border rounded-lg px-3 py-2" rows={2} required placeholder="مبررات إصدار القرار" />
            </div>
          </div>

          <div className="flex justify-end gap-3 pt-4 border-t">
            <button type="button" onClick={() => setShowDecision(null)} className="px-4 py-2 border rounded-lg hover:bg-gray-50">إلغاء</button>
            <button type="submit" disabled={saving} className="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50">
              {saving ? 'جاري الإصدار...' : 'إصدار القرار'}
            </button>
          </div>
        </form>
      </Modal>

      {/* Pagination */}
      {meta.last_page > 1 && activeTab !== 'types' && (
        <div className="flex justify-center gap-2">
          {Array.from({ length: meta.last_page }, (_, i) => (
            <button key={i + 1} onClick={() => setPage(i + 1)}
              className={`px-3 py-1 rounded ${page === i + 1 ? 'bg-indigo-600 text-white' : 'bg-white border hover:bg-gray-50'}`}>
              {i + 1}
            </button>
          ))}
        </div>
      )}
    </div>
  );
}
