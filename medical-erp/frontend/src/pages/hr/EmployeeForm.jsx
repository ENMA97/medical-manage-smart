import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import toast from 'react-hot-toast';
import RegionCountySelect from '../../components/common/RegionCountySelect';
import api from '../../services/api';

const initialFormData = {
  employee_number: '',
  name: '',
  name_ar: '',
  email: '',
  phone: '',
  national_id: '',
  birth_date: '',
  gender: 'male',
  nationality: '',
  department_id: '',
  position_id: '',
  hire_date: '',
  status: 'active',
  bank_name: '',
  iban: '',
  address: '',
  region_id: '',
  county_id: '',
  emergency_contact: '',
  emergency_phone: '',
};

export default function EmployeeForm() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const isEditing = !!id;

  const [formData, setFormData] = useState(initialFormData);
  const [errors, setErrors] = useState({});

  const { data: employeeData, isLoading } = useQuery({
    queryKey: ['employee', id],
    queryFn: () => api.get(`/employees/${id}`).then((r) => r.data.data),
    enabled: isEditing,
  });

  useEffect(() => {
    if (employeeData) {
      setFormData({
        ...initialFormData,
        ...employeeData,
        region_id: employeeData.county?.region_id || '',
        county_id: employeeData.county_id || '',
      });
    }
  }, [employeeData]);

  const mutation = useMutation({
    mutationFn: (data) =>
      isEditing ? api.put(`/employees/${id}`, data) : api.post('/employees', data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['employees'] });
      toast.success(isEditing ? 'تم تحديث الموظف بنجاح' : 'تم إضافة الموظف بنجاح');
      navigate('/hr/employees');
    },
    onError: (error) => {
      if (error.response?.status === 422) {
        setErrors(error.response.data.errors || {});
      } else {
        toast.error('حدث خطأ أثناء حفظ البيانات');
      }
    },
  });

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    if (errors[name]) {
      setErrors((prev) => ({ ...prev, [name]: undefined }));
    }
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    const { region_id, ...submitData } = formData;
    mutation.mutate(submitData);
  };

  if (isEditing && isLoading) {
    return (
      <div className="flex justify-center py-12">
        <div className="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  return (
    <div className="max-w-4xl mx-auto">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">
          {isEditing ? 'تعديل بيانات الموظف' : 'إضافة موظف جديد'}
        </h1>
        <button
          type="button"
          onClick={() => navigate('/hr/employees')}
          className="text-sm text-gray-600 hover:text-gray-900"
        >
          العودة للقائمة
        </button>
      </div>

      <form onSubmit={handleSubmit} className="space-y-8 bg-white shadow rounded-lg p-6">
        {/* Basic Info */}
        <fieldset>
          <legend className="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
            البيانات الأساسية
          </legend>
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <InputField label="رقم الموظف" name="employee_number" value={formData.employee_number} onChange={handleChange} error={errors.employee_number} required />
            <InputField label="الاسم (عربي)" name="name_ar" value={formData.name_ar} onChange={handleChange} error={errors.name_ar} />
            <InputField label="Name (English)" name="name" value={formData.name} onChange={handleChange} error={errors.name} required />
            <InputField label="البريد الإلكتروني" name="email" type="email" value={formData.email} onChange={handleChange} error={errors.email} required />
            <InputField label="رقم الهاتف" name="phone" value={formData.phone} onChange={handleChange} error={errors.phone} />
            <InputField label="رقم الهوية" name="national_id" value={formData.national_id} onChange={handleChange} error={errors.national_id} required />
            <InputField label="تاريخ الميلاد" name="birth_date" type="date" value={formData.birth_date} onChange={handleChange} error={errors.birth_date} />
            <SelectField label="الجنس" name="gender" value={formData.gender} onChange={handleChange} error={errors.gender} options={[{ value: 'male', label: 'ذكر' }, { value: 'female', label: 'أنثى' }]} />
            <InputField label="الجنسية" name="nationality" value={formData.nationality} onChange={handleChange} error={errors.nationality} />
            <InputField label="تاريخ التعيين" name="hire_date" type="date" value={formData.hire_date} onChange={handleChange} error={errors.hire_date} required />
          </div>
        </fieldset>

        {/* Location */}
        <fieldset>
          <legend className="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
            الموقع الجغرافي
          </legend>
          <RegionCountySelect
            regionId={formData.region_id}
            countyId={formData.county_id}
            onRegionChange={(value) => setFormData((prev) => ({ ...prev, region_id: value }))}
            onCountyChange={(value) => setFormData((prev) => ({ ...prev, county_id: value }))}
            errors={errors}
          />
          <div className="mt-4">
            <label htmlFor="address" className="block text-sm font-medium text-gray-700 mb-1">العنوان التفصيلي</label>
            <textarea
              id="address"
              name="address"
              rows={2}
              value={formData.address}
              onChange={handleChange}
              className="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500"
            />
          </div>
        </fieldset>

        {/* Emergency Contact */}
        <fieldset>
          <legend className="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
            جهة اتصال الطوارئ
          </legend>
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <InputField label="اسم جهة الاتصال" name="emergency_contact" value={formData.emergency_contact} onChange={handleChange} error={errors.emergency_contact} />
            <InputField label="رقم الطوارئ" name="emergency_phone" value={formData.emergency_phone} onChange={handleChange} error={errors.emergency_phone} />
          </div>
        </fieldset>

        {/* Bank */}
        <fieldset>
          <legend className="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
            البيانات البنكية
          </legend>
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <InputField label="اسم البنك" name="bank_name" value={formData.bank_name} onChange={handleChange} error={errors.bank_name} />
            <InputField label="IBAN" name="iban" value={formData.iban} onChange={handleChange} error={errors.iban} />
          </div>
        </fieldset>

        {/* Submit */}
        <div className="flex justify-end gap-3 pt-4 border-t">
          <button
            type="button"
            onClick={() => navigate('/hr/employees')}
            className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
          >
            إلغاء
          </button>
          <button
            type="submit"
            disabled={mutation.isPending}
            className="rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 disabled:opacity-50"
          >
            {mutation.isPending ? 'جاري الحفظ...' : isEditing ? 'تحديث' : 'حفظ'}
          </button>
        </div>
      </form>
    </div>
  );
}

function InputField({ label, name, type = 'text', value, onChange, error, required }) {
  return (
    <div>
      <label htmlFor={name} className="block text-sm font-medium text-gray-700 mb-1">
        {label} {required && <span className="text-red-500">*</span>}
      </label>
      <input
        id={name}
        name={name}
        type={type}
        value={value}
        onChange={onChange}
        required={required}
        className={`block w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 ${
          error ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-primary-500'
        }`}
      />
      {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
    </div>
  );
}

function SelectField({ label, name, value, onChange, error, options }) {
  return (
    <div>
      <label htmlFor={name} className="block text-sm font-medium text-gray-700 mb-1">{label}</label>
      <select
        id={name}
        name={name}
        value={value}
        onChange={onChange}
        className={`block w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 ${
          error ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-primary-500'
        }`}
      >
        {options.map((opt) => (
          <option key={opt.value} value={opt.value}>{opt.label}</option>
        ))}
      </select>
      {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
    </div>
  );
}
