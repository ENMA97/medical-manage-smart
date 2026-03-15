import { useState, useEffect, useCallback } from 'react';
import toast from 'react-hot-toast';
import contractService from '../services/contractService';
import employeeService from '../services/employeeService';
import Modal from '../components/ui/Modal';

const typeLabels = { permanent: 'دائم', temporary: 'مؤقت', part_time: 'دوام جزئي', probation: 'تجريبي', full_time: 'دوام كامل', tamheer: 'تمهير', percentage: 'نسبة', locum: 'بديل' };
const statusLabels = { active: 'ساري', expired: 'منتهي', terminated: 'ملغي', renewed: 'مجدد', draft: 'مسودة', pending_approval: 'بانتظار الموافقة' };
const statusColors = { active: 'bg-green-100 text-green-700', expired: 'bg-red-100 text-red-700', terminated: 'bg-gray-100 text-gray-600', renewed: 'bg-teal-100 text-teal-700', draft: 'bg-yellow-100 text-yellow-700', pending_approval: 'bg-orange-100 text-orange-700' };

const emptyForm = {
  employee_id: '', contract_type: 'full_time', start_date: '', end_date: '',
  basic_salary: '', housing_allowance: '', transport_allowance: '',
  food_allowance: '', phone_allowance: '', other_allowances: '',
  duration_months: '12', annual_leave_days: '21', notice_period_days: '30',
};

export default function Contracts() {
  const [contracts, setContracts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [meta, setMeta] = useState({});
  const [showCreate, setShowCreate] = useState(false);
  const [renewTarget, setRenewTarget] = useState(null);
  const [form, setForm] = useState(emptyForm);
  const [saving, setSaving] = useState(false);
  const [employees, setEmployees] = useState([]);

  const fetchContracts = useCallback(async () => {
    setLoading(true);
    try {
      const { data } = await contractService.getAll({ search, page, per_page: 15 });
      setContracts(data.data);
      setMeta(data.meta || {});
    } catch {
      toast.error('حدث خطأ في تحميل العقود');
    } finally {
      setLoading(false);
    }
  }, [search, page]);

  useEffect(() => { fetchContracts(); }, [fetchContracts]);
  useEffect(() => { setPage(1); }, [search]);

  async function loadEmployees() {
    if (employees.length > 0) return;
    try {
      const { data } = await employeeService.getAll({ per_page: 200 });
      setEmployees(data.data?.data || data.data || []);
    } catch { /* silent */ }
  }

  function openCreate() {
    loadEmployees();
    setForm(emptyForm);
    setShowCreate(true);
  }

  function openRenew(contract) {
    setRenewTarget(contract);
    setForm({
      ...emptyForm,
      start_date: contract.end_date || '',
      end_date: '',
      basic_salary: contract.basic_salary || '',
      housing_allowance: contract.housing_allowance || '',
      transport_allowance: contract.transport_allowance || '',
      food_allowance: contract.food_allowance || '',
      phone_allowance: contract.phone_allowance || '',
      other_allowances: contract.other_allowances || '',
      duration_months: contract.duration_months || '12',
    });
  }

  async function handleCreate(e) {
    e.preventDefault();
    setSaving(true);
    try {
      await contractService.create({
        ...form,
        basic_salary: Number(form.basic_salary) || 0,
        housing_allowance: Number(form.housing_allowance) || 0,
        transport_allowance: Number(form.transport_allowance) || 0,
        food_allowance: Number(form.food_allowance) || 0,
        phone_allowance: Number(form.phone_allowance) || 0,
        other_allowances: Number(form.other_allowances) || 0,
      });
      toast.success('تم إنشاء العقد بنجاح');
      setShowCreate(false);
      fetchContracts();
    } catch (err) {
      toast.error(err.response?.data?.message || 'حدث خطأ');
    } finally {
      setSaving(false);
    }
  }

  async function handleRenew(e) {
    e.preventDefault();
    setSaving(true);
    try {
      await contractService.renew(renewTarget.id, {
        start_date: form.start_date,
        end_date: form.end_date || undefined,
        duration_months: Number(form.duration_months) || undefined,
        basic_salary: Number(form.basic_salary) || undefined,
        housing_allowance: Number(form.housing_allowance) || undefined,
        transport_allowance: Number(form.transport_allowance) || undefined,
        food_allowance: Number(form.food_allowance) || undefined,
        phone_allowance: Number(form.phone_allowance) || undefined,
        other_allowances: Number(form.other_allowances) || undefined,
      });
      toast.success('تم تجديد العقد بنجاح');
      setRenewTarget(null);
      fetchContracts();
    } catch (err) {
      toast.error(err.response?.data?.message || 'حدث خطأ');
    } finally {
      setSaving(false);
    }
  }

  function set(key, value) { setForm((f) => ({ ...f, [key]: value })); }

  const Input = ({ label, name, type = 'text', required = false }) => (
    <div>
      <label className="block text-xs text-gray-600 mb-1">{label}</label>
      <input
        type={type} value={form[name]} onChange={(e) => set(name, e.target.value)}
        required={required}
        className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent"
      />
    </div>
  );

  const SalaryFields = () => (
    <div className="grid grid-cols-2 gap-3">
      <Input label="الراتب الأساسي" name="basic_salary" type="number" required />
      <Input label="بدل السكن" name="housing_allowance" type="number" />
      <Input label="بدل النقل" name="transport_allowance" type="number" />
      <Input label="بدل الطعام" name="food_allowance" type="number" />
      <Input label="بدل الهاتف" name="phone_allowance" type="number" />
      <Input label="بدلات أخرى" name="other_allowances" type="number" />
    </div>
  );

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-bold text-gray-800">العقود</h1>
        <button onClick={openCreate} className="px-4 py-2 bg-teal-600 text-white text-sm rounded-lg hover:bg-teal-700 transition-colors">
          عقد جديد
        </button>
      </div>

      <div className="relative">
        <input
          type="text" placeholder="البحث..." value={search} onChange={(e) => setSearch(e.target.value)}
          className="w-full sm:w-80 pl-4 pr-10 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent"
        />
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center py-16">
            <div className="w-8 h-8 border-4 border-teal-200 border-t-teal-600 rounded-full animate-spin" />
          </div>
        ) : contracts.length === 0 ? (
          <div className="text-center py-16 text-gray-500">لا يوجد عقود</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead className="bg-gray-50 border-b border-gray-100">
                <tr>
                  <th className="text-right px-4 py-3 font-medium text-gray-600">الموظف</th>
                  <th className="text-right px-4 py-3 font-medium text-gray-600 hidden sm:table-cell">النوع</th>
                  <th className="text-right px-4 py-3 font-medium text-gray-600">من</th>
                  <th className="text-right px-4 py-3 font-medium text-gray-600">إلى</th>
                  <th className="text-right px-4 py-3 font-medium text-gray-600">الحالة</th>
                  <th className="px-4 py-3"></th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {contracts.map((c) => (
                  <tr key={c.id} className="hover:bg-gray-50">
                    <td className="px-4 py-3 font-medium text-gray-800">{c.employee?.full_name}</td>
                    <td className="px-4 py-3 text-gray-600 hidden sm:table-cell">{typeLabels[c.type] || typeLabels[c.contract_type] || c.type || c.contract_type}</td>
                    <td className="px-4 py-3 text-gray-600">{c.start_date}</td>
                    <td className="px-4 py-3 text-gray-600">{c.end_date || '—'}</td>
                    <td className="px-4 py-3">
                      <span className={`inline-block px-2 py-0.5 rounded-full text-xs font-medium ${statusColors[c.status] || 'bg-gray-100 text-gray-600'}`}>
                        {statusLabels[c.status] || c.status}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      {(c.status === 'active' || c.status === 'expired') && (
                        <button onClick={() => openRenew(c)} className="text-xs text-teal-600 hover:text-teal-800 font-medium">
                          تجديد
                        </button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {meta.last_page > 1 && (
          <div className="flex items-center justify-between px-4 py-3 border-t border-gray-100">
            <p className="text-xs text-gray-500">عرض {meta.from}–{meta.to} من {meta.total}</p>
            <div className="flex gap-1">
              <button disabled={page <= 1} onClick={() => setPage(page - 1)} className="px-3 py-1 text-sm rounded-lg border border-gray-200 disabled:opacity-50 hover:bg-gray-50">السابق</button>
              <button disabled={page >= meta.last_page} onClick={() => setPage(page + 1)} className="px-3 py-1 text-sm rounded-lg border border-gray-200 disabled:opacity-50 hover:bg-gray-50">التالي</button>
            </div>
          </div>
        )}
      </div>

      {/* Create Contract Modal */}
      <Modal open={showCreate} onClose={() => setShowCreate(false)} title="إنشاء عقد جديد" maxWidth="max-w-xl">
        <form onSubmit={handleCreate} className="space-y-4">
          <div>
            <label className="block text-xs text-gray-600 mb-1">الموظف</label>
            <select
              value={form.employee_id} onChange={(e) => set('employee_id', e.target.value)}
              required
              className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent"
            >
              <option value="">اختر الموظف</option>
              {employees.map((emp) => (
                <option key={emp.id} value={emp.id}>
                  {emp.full_name || `${emp.first_name_ar || emp.first_name} ${emp.last_name_ar || emp.last_name}`} — {emp.employee_number}
                </option>
              ))}
            </select>
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-xs text-gray-600 mb-1">نوع العقد</label>
              <select
                value={form.contract_type} onChange={(e) => set('contract_type', e.target.value)}
                className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent"
              >
                {Object.entries(typeLabels).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
              </select>
            </div>
            <Input label="مدة العقد (أشهر)" name="duration_months" type="number" />
          </div>
          <div className="grid grid-cols-2 gap-3">
            <Input label="تاريخ البداية" name="start_date" type="date" required />
            <Input label="تاريخ النهاية" name="end_date" type="date" />
          </div>
          <SalaryFields />
          <div className="grid grid-cols-2 gap-3">
            <Input label="أيام الإجازة السنوية" name="annual_leave_days" type="number" />
            <Input label="فترة الإشعار (أيام)" name="notice_period_days" type="number" />
          </div>
          <div className="flex gap-2 justify-end pt-2">
            <button type="button" onClick={() => setShowCreate(false)} className="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">إلغاء</button>
            <button type="submit" disabled={saving} className="px-4 py-2 text-sm text-white bg-teal-600 rounded-lg hover:bg-teal-700 disabled:opacity-50">
              {saving ? 'جاري الحفظ...' : 'إنشاء العقد'}
            </button>
          </div>
        </form>
      </Modal>

      {/* Renew Contract Modal */}
      <Modal open={!!renewTarget} onClose={() => setRenewTarget(null)} title={`تجديد عقد — ${renewTarget?.employee?.full_name || ''}`} maxWidth="max-w-xl">
        <form onSubmit={handleRenew} className="space-y-4">
          <div className="grid grid-cols-2 gap-3">
            <Input label="تاريخ البداية الجديد" name="start_date" type="date" required />
            <Input label="تاريخ النهاية" name="end_date" type="date" />
          </div>
          <Input label="مدة العقد (أشهر)" name="duration_months" type="number" />
          <SalaryFields />
          <div className="flex gap-2 justify-end pt-2">
            <button type="button" onClick={() => setRenewTarget(null)} className="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">إلغاء</button>
            <button type="submit" disabled={saving} className="px-4 py-2 text-sm text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-50">
              {saving ? 'جاري التجديد...' : 'تجديد العقد'}
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
}
