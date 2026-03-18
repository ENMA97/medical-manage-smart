import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';
import employeeService from '../services/employeeService';
import departmentService from '../services/departmentService';

const initialForm = {
  employee_number: '',
  full_name: '',
  phone: '',
  email: '',
  national_id: '',
  department_id: '',
  date_of_birth: '',
  hire_date: '',
  gender: '',
  nationality: '',
  marital_status: '',
  salary: '',
  bank_name: '',
  bank_iban: '',
  address: '',
  emergency_contact_name: '',
  emergency_contact_phone: '',
};

export default function EmployeeForm() {
  const { id } = useParams();
  const navigate = useNavigate();
  const isEdit = !!id;

  const [form, setForm] = useState(initialForm);
  const [departments, setDepartments] = useState([]);
  const [loading, setLoading] = useState(isEdit);
  const [saving, setSaving] = useState(false);
  const [errors, setErrors] = useState({});

  useEffect(() => {
    departmentService.getAll().then(({ data }) => setDepartments(data.data || [])).catch(() => {});

    if (isEdit) {
      employeeService.getById(id)
        .then(({ data }) => {
          const emp = data.data;
          setForm({
            employee_number: emp.employee_number || '',
            full_name: emp.full_name || '',
            phone: emp.user?.phone || '',
            email: emp.email || '',
            national_id: emp.national_id || '',
            department_id: emp.department_id || '',
            date_of_birth: emp.date_of_birth || '',
            hire_date: emp.hire_date || '',
            gender: emp.gender || '',
            nationality: emp.nationality || '',
            marital_status: emp.marital_status || '',
            salary: emp.salary || '',
            bank_name: emp.bank_name || '',
            bank_iban: emp.bank_iban || '',
            address: emp.address || '',
            emergency_contact_name: emp.emergency_contact_name || '',
            emergency_contact_phone: emp.emergency_contact_phone || '',
          });
        })
        .catch(() => {
          toast.error('حدث خطأ في تحميل بيانات الموظف');
          navigate('/employees');
        })
        .finally(() => setLoading(false));
    }
  }, [id, isEdit, navigate]);

  function handleChange(field, value) {
    setForm((prev) => ({ ...prev, [field]: value }));
    if (errors[field]) setErrors((prev) => ({ ...prev, [field]: null }));
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setErrors({});
    setSaving(true);

    try {
      if (isEdit) {
        await employeeService.update(id, form);
        toast.success('تم تحديث بيانات الموظف');
      } else {
        await employeeService.create(form);
        toast.success('تم إضافة الموظف بنجاح');
      }
      navigate('/employees');
    } catch (err) {
      if (err.response?.status === 422) {
        setErrors(err.response.data.errors || {});
        toast.error('يرجى تصحيح الأخطاء');
      } else {
        toast.error(err.response?.data?.message || 'حدث خطأ');
      }
    } finally {
      setSaving(false);
    }
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center py-20">
        <div className="w-8 h-8 border-4 border-teal-200 border-t-teal-600 rounded-full animate-spin" />
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <button onClick={() => navigate('/employees')} className="text-sm text-teal-600 hover:text-teal-800 flex items-center gap-1">
        <svg className="w-4 h-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
        </svg>
        العودة للموظفين
      </button>

      <h1 className="text-xl font-bold text-gray-800">{isEdit ? 'تعديل بيانات الموظف' : 'إضافة موظف جديد'}</h1>

      <form onSubmit={handleSubmit} className="space-y-4">
        {/* Basic Info */}
        <Section title="المعلومات الأساسية">
          <Field label="الرقم الوظيفي *" error={errors.employee_number}>
            <input type="text" value={form.employee_number} onChange={(e) => handleChange('employee_number', e.target.value)} required className={inputClass(errors.employee_number)} />
          </Field>
          <Field label="الاسم الكامل *" error={errors.full_name}>
            <input type="text" value={form.full_name} onChange={(e) => handleChange('full_name', e.target.value)} required className={inputClass(errors.full_name)} />
          </Field>
          <Field label="رقم الهاتف *" error={errors.phone}>
            <input type="tel" value={form.phone} onChange={(e) => handleChange('phone', e.target.value)} required dir="ltr" className={inputClass(errors.phone)} />
          </Field>
          <Field label="البريد الإلكتروني" error={errors.email}>
            <input type="email" value={form.email} onChange={(e) => handleChange('email', e.target.value)} dir="ltr" className={inputClass(errors.email)} />
          </Field>
        </Section>

        {/* Personal Info */}
        <Section title="المعلومات الشخصية">
          <Field label="رقم الهوية" error={errors.national_id}>
            <input type="text" value={form.national_id} onChange={(e) => handleChange('national_id', e.target.value)} className={inputClass(errors.national_id)} />
          </Field>
          <Field label="تاريخ الميلاد" error={errors.date_of_birth}>
            <input type="date" value={form.date_of_birth} onChange={(e) => handleChange('date_of_birth', e.target.value)} className={inputClass(errors.date_of_birth)} />
          </Field>
          <Field label="الجنس" error={errors.gender}>
            <select value={form.gender} onChange={(e) => handleChange('gender', e.target.value)} className={inputClass(errors.gender)}>
              <option value="">— اختر —</option>
              <option value="male">ذكر</option>
              <option value="female">أنثى</option>
            </select>
          </Field>
          <Field label="الجنسية" error={errors.nationality}>
            <input type="text" value={form.nationality} onChange={(e) => handleChange('nationality', e.target.value)} className={inputClass(errors.nationality)} />
          </Field>
          <Field label="الحالة الاجتماعية" error={errors.marital_status}>
            <select value={form.marital_status} onChange={(e) => handleChange('marital_status', e.target.value)} className={inputClass(errors.marital_status)}>
              <option value="">— اختر —</option>
              <option value="single">أعزب</option>
              <option value="married">متزوج</option>
              <option value="divorced">مطلق</option>
              <option value="widowed">أرمل</option>
            </select>
          </Field>
          <Field label="العنوان" error={errors.address} full>
            <input type="text" value={form.address} onChange={(e) => handleChange('address', e.target.value)} className={inputClass(errors.address)} />
          </Field>
        </Section>

        {/* Employment Info */}
        <Section title="معلومات العمل">
          <Field label="القسم *" error={errors.department_id}>
            <select value={form.department_id} onChange={(e) => handleChange('department_id', e.target.value)} required className={inputClass(errors.department_id)}>
              <option value="">— اختر القسم —</option>
              {departments.map((d) => (
                <option key={d.id} value={d.id}>{d.name_ar}</option>
              ))}
            </select>
          </Field>
          <Field label="تاريخ التعيين *" error={errors.hire_date}>
            <input type="date" value={form.hire_date} onChange={(e) => handleChange('hire_date', e.target.value)} required className={inputClass(errors.hire_date)} />
          </Field>
          <Field label="الراتب" error={errors.salary}>
            <input type="number" value={form.salary} onChange={(e) => handleChange('salary', e.target.value)} min="0" step="0.01" dir="ltr" className={inputClass(errors.salary)} />
          </Field>
        </Section>

        {/* Bank Info */}
        <Section title="المعلومات البنكية">
          <Field label="اسم البنك" error={errors.bank_name}>
            <input type="text" value={form.bank_name} onChange={(e) => handleChange('bank_name', e.target.value)} className={inputClass(errors.bank_name)} />
          </Field>
          <Field label="رقم الآيبان (IBAN)" error={errors.bank_iban}>
            <input type="text" value={form.bank_iban} onChange={(e) => handleChange('bank_iban', e.target.value)} dir="ltr" className={inputClass(errors.bank_iban)} />
          </Field>
        </Section>

        {/* Emergency Contact */}
        <Section title="جهة الاتصال في حالة الطوارئ">
          <Field label="الاسم" error={errors.emergency_contact_name}>
            <input type="text" value={form.emergency_contact_name} onChange={(e) => handleChange('emergency_contact_name', e.target.value)} className={inputClass(errors.emergency_contact_name)} />
          </Field>
          <Field label="رقم الهاتف" error={errors.emergency_contact_phone}>
            <input type="tel" value={form.emergency_contact_phone} onChange={(e) => handleChange('emergency_contact_phone', e.target.value)} dir="ltr" className={inputClass(errors.emergency_contact_phone)} />
          </Field>
        </Section>

        {/* Actions */}
        <div className="flex gap-3 justify-end pt-2">
          <button type="button" onClick={() => navigate('/employees')} className="px-6 py-2.5 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">
            إلغاء
          </button>
          <button type="submit" disabled={saving} className="px-6 py-2.5 text-sm bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50 font-medium">
            {saving ? 'جاري الحفظ...' : isEdit ? 'حفظ التعديلات' : 'إضافة الموظف'}
          </button>
        </div>
      </form>
    </div>
  );
}

function Section({ title, children }) {
  return (
    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
      <h2 className="text-base font-semibold text-gray-800 mb-4">{title}</h2>
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        {children}
      </div>
    </div>
  );
}

function Field({ label, error, children, full }) {
  return (
    <div className={full ? 'sm:col-span-2' : ''}>
      <label className="block text-sm font-medium text-gray-700 mb-1">{label}</label>
      {children}
      {error && <p className="text-xs text-red-500 mt-1">{Array.isArray(error) ? error[0] : error}</p>}
    </div>
  );
}

function inputClass(hasError) {
  return `w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent ${
    hasError ? 'border-red-300 bg-red-50' : 'border-gray-200'
  }`;
}
