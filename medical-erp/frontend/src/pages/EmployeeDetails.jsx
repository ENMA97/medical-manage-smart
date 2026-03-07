import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';
import employeeService from '../services/employeeService';

export default function EmployeeDetails() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [employee, setEmployee] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function load() {
      try {
        const { data } = await employeeService.getById(id);
        setEmployee(data.data);
      } catch {
        toast.error('حدث خطأ في تحميل بيانات الموظف');
        navigate('/employees');
      } finally {
        setLoading(false);
      }
    }
    load();
  }, [id, navigate]);

  if (loading) {
    return (
      <div className="flex items-center justify-center py-20">
        <div className="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin" />
      </div>
    );
  }

  if (!employee) return null;

  const InfoRow = ({ label, value }) => (
    <div className="flex justify-between py-2.5 border-b border-gray-50 last:border-0">
      <span className="text-gray-500 text-sm">{label}</span>
      <span className="text-gray-800 text-sm font-medium">{value || '—'}</span>
    </div>
  );

  return (
    <div className="space-y-4">
      <button onClick={() => navigate('/employees')} className="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1">
        <svg className="w-4 h-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
        </svg>
        العودة للموظفين
      </button>

      {/* Header */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
        <div className="flex items-center gap-4">
          <div className="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xl font-bold">
            {employee.full_name?.[0]}
          </div>
          <div>
            <h1 className="text-lg font-bold text-gray-800">{employee.full_name}</h1>
            <p className="text-sm text-gray-500">{employee.employee_number} — {employee.department?.name_ar}</p>
          </div>
        </div>
      </div>

      {/* Personal Info */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
        <h2 className="text-base font-semibold text-gray-800 mb-3">المعلومات الشخصية</h2>
        <InfoRow label="رقم الهوية" value={employee.national_id} />
        <InfoRow label="تاريخ الميلاد" value={employee.date_of_birth} />
        <InfoRow label="الجنس" value={employee.gender === 'male' ? 'ذكر' : employee.gender === 'female' ? 'أنثى' : null} />
        <InfoRow label="الجنسية" value={employee.nationality} />
        <InfoRow label="الحالة الاجتماعية" value={employee.marital_status} />
        <InfoRow label="العنوان" value={employee.address} />
      </div>

      {/* Employment Info */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
        <h2 className="text-base font-semibold text-gray-800 mb-3">معلومات العمل</h2>
        <InfoRow label="القسم" value={employee.department?.name_ar} />
        <InfoRow label="المسمى الوظيفي" value={employee.position?.title_ar} />
        <InfoRow label="تاريخ التعيين" value={employee.hire_date} />
        <InfoRow label="الراتب" value={employee.salary ? `${Number(employee.salary).toLocaleString()} ريال` : null} />
        <InfoRow label="البنك" value={employee.bank_name} />
        <InfoRow label="IBAN" value={employee.bank_iban} />
      </div>

      {/* Emergency Contact */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
        <h2 className="text-base font-semibold text-gray-800 mb-3">جهة الاتصال في حالة الطوارئ</h2>
        <InfoRow label="الاسم" value={employee.emergency_contact_name} />
        <InfoRow label="الهاتف" value={employee.emergency_contact_phone} />
      </div>
    </div>
  );
}
