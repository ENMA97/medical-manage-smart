import React, { useState, useEffect } from 'react';

const moduleLabels = {
  hr: 'الموارد البشرية',
  inventory: 'المخزون',
  roster: 'الجداول',
  finance: 'المالية',
  payroll: 'الرواتب',
  system: 'النظام',
  leaves: 'الإجازات',
};

const mockRoles = [
  { id: 1, name_ar: 'مدير النظام', name_en: 'Admin', description_ar: 'صلاحيات كاملة على جميع الوحدات', users_count: 2, permissions: ['hr', 'inventory', 'roster', 'finance', 'payroll', 'system', 'leaves'], status: 'active', created_at: '2024-01-01' },
  { id: 2, name_ar: 'مدير الموارد البشرية', name_en: 'HR Manager', description_ar: 'إدارة شؤون الموظفين والإجازات', users_count: 3, permissions: ['hr', 'roster', 'leaves'], status: 'active', created_at: '2024-01-15' },
  { id: 3, name_ar: 'مدير المالية', name_en: 'Finance Manager', description_ar: 'إدارة الحسابات والتقارير المالية', users_count: 2, permissions: ['finance', 'payroll'], status: 'active', created_at: '2024-02-01' },
  { id: 4, name_ar: 'طبيب', name_en: 'Doctor', description_ar: 'الوصول للملفات الطبية والجدولة', users_count: 15, permissions: ['roster', 'leaves'], status: 'active', created_at: '2024-03-01' },
  { id: 5, name_ar: 'ممرض/ة', name_en: 'Nurse', description_ar: 'الوصول للجداول والمخزون الطبي', users_count: 25, permissions: ['roster', 'inventory', 'leaves'], status: 'active', created_at: '2024-03-15' },
  { id: 6, name_ar: 'صيدلي', name_en: 'Pharmacist', description_ar: 'إدارة المخزون الصيدلاني', users_count: 4, permissions: ['inventory', 'leaves'], status: 'active', created_at: '2024-04-01' },
  { id: 7, name_ar: 'فني مختبر', name_en: 'Lab Technician', description_ar: 'الوصول للمختبرات والمخزون', users_count: 6, permissions: ['inventory', 'leaves'], status: 'inactive', created_at: '2024-05-01' },
  { id: 8, name_ar: 'موظف استقبال', name_en: 'Receptionist', description_ar: 'إدارة المواعيد والاستقبال', users_count: 3, permissions: ['leaves'], status: 'active', created_at: '2024-06-01' },
];

const statusLabels = { active: 'مفعّل', inactive: 'معطّل' };
const statusColors = { active: 'bg-green-100 text-green-700', inactive: 'bg-gray-100 text-gray-700' };

const roleColorPalette = [
  'bg-red-100 text-red-700', 'bg-blue-100 text-blue-700', 'bg-green-100 text-green-700',
  'bg-purple-100 text-purple-700', 'bg-pink-100 text-pink-700', 'bg-yellow-100 text-yellow-700',
  'bg-indigo-100 text-indigo-700', 'bg-gray-100 text-gray-700'
];

export default function RolesPage() {
  const [roles, setRoles] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterStatus, setFilterStatus] = useState('');
  const [showModal, setShowModal] = useState(false);
  const [showDetailModal, setShowDetailModal] = useState(false);
  const [showUsersModal, setShowUsersModal] = useState(false);
  const [selectedRole, setSelectedRole] = useState(null);
  const [editMode, setEditMode] = useState(false);
  const [formData, setFormData] = useState({ name_ar: '', name_en: '', description_ar: '', permissions: [] });

  useEffect(() => {
    setTimeout(() => { setRoles(mockRoles); setLoading(false); }, 400);
  }, []);

  const filtered = roles.filter(r => {
    if (filterStatus && r.status !== filterStatus) return false;
    if (searchTerm && !r.name_ar.includes(searchTerm) && !r.name_en.toLowerCase().includes(searchTerm.toLowerCase())) return false;
    return true;
  });

  const handleSave = () => {
    if (!formData.name_ar || !formData.name_en) { alert('الرجاء ملء الحقول المطلوبة'); return; }
    if (editMode && selectedRole) {
      setRoles(prev => prev.map(r => r.id === selectedRole.id ? { ...r, ...formData } : r));
    } else {
      setRoles(prev => [...prev, { id: Math.max(...prev.map(r => r.id)) + 1, ...formData, users_count: 0, status: 'active', created_at: new Date().toISOString().split('T')[0] }]);
    }
    setShowModal(false);
    resetForm();
  };

  const resetForm = () => {
    setFormData({ name_ar: '', name_en: '', description_ar: '', permissions: [] });
    setEditMode(false);
    setSelectedRole(null);
  };

  const openEdit = (role) => {
    setFormData({ name_ar: role.name_ar, name_en: role.name_en, description_ar: role.description_ar, permissions: [...role.permissions] });
    setSelectedRole(role);
    setEditMode(true);
    setShowModal(true);
  };

  const togglePermission = (mod) => {
    setFormData(prev => ({
      ...prev,
      permissions: prev.permissions.includes(mod) ? prev.permissions.filter(p => p !== mod) : [...prev.permissions, mod]
    }));
  };

  const toggleStatus = (id) => {
    setRoles(prev => prev.map(r => r.id === id ? { ...r, status: r.status === 'active' ? 'inactive' : 'active' } : r));
  };

  // Mock users for each role
  const mockRoleUsers = {
    1: [{ name: 'مدير النظام', email: 'admin@medical.sa' }, { name: 'محمد السعيد', email: 'mohammed@medical.sa' }],
    2: [{ name: 'فهد العمري', email: 'fahd@medical.sa' }, { name: 'سلمان الراشد', email: 'salman@medical.sa' }, { name: 'هدى المطيري', email: 'huda@medical.sa' }],
    3: [{ name: 'نوال الحربي', email: 'nawal@medical.sa' }, { name: 'عمر الفيصل', email: 'omar@medical.sa' }],
    4: [{ name: 'د. أحمد الشمري', email: 'ahmed@medical.sa' }, { name: 'د. فاطمة العلي', email: 'fatima@medical.sa' }],
    5: [{ name: 'سارة القحطاني', email: 'sara@medical.sa' }, { name: 'ليلى العنزي', email: 'layla@medical.sa' }],
    6: [{ name: 'خالد الغامدي', email: 'khalid@medical.sa' }],
    7: [{ name: 'مريم العتيبي', email: 'mariam@medical.sa' }],
    8: [{ name: 'عبدالله الدوسري', email: 'abdullah@medical.sa' }],
  };

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div></div>;

  return (
    <div className="space-y-6" dir="rtl">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">إدارة الأدوار</h1>
          <p className="text-gray-600 mt-1">إدارة أدوار المستخدمين والصلاحيات</p>
        </div>
        <button onClick={() => { resetForm(); setShowModal(true); }} className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">+ إضافة دور</button>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div className="bg-white rounded-lg shadow p-4"><p className="text-xs text-gray-500">إجمالي الأدوار</p><p className="text-2xl font-bold mt-1">{roles.length}</p></div>
        <div className="bg-white rounded-lg shadow p-4"><p className="text-xs text-gray-500">أدوار مفعّلة</p><p className="text-2xl font-bold text-green-600 mt-1">{roles.filter(r => r.status === 'active').length}</p></div>
        <div className="bg-white rounded-lg shadow p-4"><p className="text-xs text-gray-500">أدوار معطّلة</p><p className="text-2xl font-bold text-gray-500 mt-1">{roles.filter(r => r.status === 'inactive').length}</p></div>
        <div className="bg-white rounded-lg shadow p-4"><p className="text-xs text-gray-500">إجمالي المستخدمين</p><p className="text-2xl font-bold text-blue-600 mt-1">{roles.reduce((s, r) => s + r.users_count, 0)}</p></div>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-lg shadow p-4 flex flex-wrap gap-4">
        <input type="text" placeholder="بحث بالاسم..." value={searchTerm} onChange={e => setSearchTerm(e.target.value)} className="border rounded-lg px-4 py-2 text-sm w-64" />
        <select value={filterStatus} onChange={e => setFilterStatus(e.target.value)} className="border rounded-lg px-4 py-2 text-sm">
          <option value="">جميع الحالات</option>
          {Object.entries(statusLabels).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
        </select>
      </div>

      {/* Roles Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {filtered.map((role, idx) => (
          <div key={role.id} className="bg-white rounded-lg shadow p-5 hover:shadow-md transition-shadow">
            <div className="flex justify-between items-start mb-3">
              <div className="flex items-center gap-3">
                <div className={`w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm ${roleColorPalette[idx % roleColorPalette.length]}`}>
                  {role.name_ar.charAt(0)}
                </div>
                <div>
                  <h3 className="font-bold text-gray-900">{role.name_ar}</h3>
                  <p className="text-xs text-gray-500" dir="ltr">{role.name_en}</p>
                </div>
              </div>
              <span className={`px-2 py-1 text-xs rounded-full ${statusColors[role.status]}`}>{statusLabels[role.status]}</span>
            </div>
            <p className="text-sm text-gray-600 mb-3">{role.description_ar}</p>
            <div className="flex flex-wrap gap-1 mb-3">
              {role.permissions.map(p => (
                <span key={p} className="px-2 py-0.5 text-xs bg-blue-50 text-blue-700 rounded">{moduleLabels[p]}</span>
              ))}
            </div>
            <div className="flex justify-between items-center pt-3 border-t">
              <button onClick={() => { setSelectedRole(role); setShowUsersModal(true); }} className="text-sm text-blue-600 hover:text-blue-800">
                {role.users_count} مستخدم
              </button>
              <div className="flex gap-2">
                <button onClick={() => { setSelectedRole(role); setShowDetailModal(true); }} className="text-blue-600 hover:text-blue-900 text-sm">عرض</button>
                <button onClick={() => openEdit(role)} className="text-yellow-600 hover:text-yellow-900 text-sm">تعديل</button>
                <button onClick={() => toggleStatus(role.id)} className="text-gray-600 hover:text-gray-900 text-sm">
                  {role.status === 'active' ? 'تعطيل' : 'تفعيل'}
                </button>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Table View */}
      <div className="bg-white rounded-lg shadow overflow-hidden">
        <div className="px-4 py-3 border-b bg-gray-50"><h3 className="font-bold text-sm text-gray-700">عرض جدول الأدوار</h3></div>
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الدور</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الوصف</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الصلاحيات</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">المستخدمين</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">تاريخ الإنشاء</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الإجراءات</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {filtered.map((role, idx) => (
                <tr key={role.id} className="hover:bg-gray-50">
                  <td className="px-4 py-4 text-sm">
                    <div className="flex items-center gap-2">
                      <div className={`w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs ${roleColorPalette[idx % roleColorPalette.length]}`}>{role.name_ar.charAt(0)}</div>
                      <div><div className="font-medium text-gray-900">{role.name_ar}</div><div className="text-xs text-gray-400" dir="ltr">{role.name_en}</div></div>
                    </div>
                  </td>
                  <td className="px-4 py-4 text-sm text-gray-500 max-w-xs truncate">{role.description_ar}</td>
                  <td className="px-4 py-4">
                    <div className="flex flex-wrap gap-1">{role.permissions.slice(0, 3).map(p => <span key={p} className="px-1.5 py-0.5 text-xs bg-blue-50 text-blue-700 rounded">{moduleLabels[p]}</span>)}
                      {role.permissions.length > 3 && <span className="px-1.5 py-0.5 text-xs bg-gray-100 text-gray-600 rounded">+{role.permissions.length - 3}</span>}
                    </div>
                  </td>
                  <td className="px-4 py-4 text-sm text-gray-700 font-medium">{role.users_count}</td>
                  <td className="px-4 py-4"><span className={`px-2 py-1 text-xs rounded-full ${statusColors[role.status]}`}>{statusLabels[role.status]}</span></td>
                  <td className="px-4 py-4 text-xs text-gray-500">{role.created_at}</td>
                  <td className="px-4 py-4 text-sm">
                    <div className="flex gap-2">
                      <button onClick={() => openEdit(role)} className="text-yellow-600 hover:text-yellow-900">تعديل</button>
                      <button onClick={() => toggleStatus(role.id)} className="text-gray-600 hover:text-gray-900">{role.status === 'active' ? 'تعطيل' : 'تفعيل'}</button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* Add/Edit Modal */}
      {showModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" dir="rtl">
            <div className="p-6 border-b"><h2 className="text-lg font-bold">{editMode ? 'تعديل دور' : 'إضافة دور جديد'}</h2></div>
            <div className="p-6 space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div><label className="block text-sm font-medium text-gray-700 mb-1">اسم الدور (عربي) *</label>
                  <input value={formData.name_ar} onChange={e => setFormData({...formData, name_ar: e.target.value})} className="w-full border rounded-lg px-3 py-2 text-sm" /></div>
                <div><label className="block text-sm font-medium text-gray-700 mb-1">اسم الدور (English) *</label>
                  <input value={formData.name_en} onChange={e => setFormData({...formData, name_en: e.target.value})} className="w-full border rounded-lg px-3 py-2 text-sm" dir="ltr" /></div>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">الوصف</label>
                <textarea value={formData.description_ar} onChange={e => setFormData({...formData, description_ar: e.target.value})} className="w-full border rounded-lg px-3 py-2 text-sm" rows={3} />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">صلاحيات الوحدات</label>
                <div className="grid grid-cols-2 gap-2">
                  {Object.entries(moduleLabels).map(([key, label]) => (
                    <label key={key} className="flex items-center gap-2 p-2 border rounded-lg hover:bg-gray-50 cursor-pointer">
                      <input type="checkbox" checked={formData.permissions.includes(key)} onChange={() => togglePermission(key)} className="rounded" />
                      <span className="text-sm">{label}</span>
                    </label>
                  ))}
                </div>
              </div>
            </div>
            <div className="p-4 border-t flex justify-end gap-3">
              <button onClick={() => { setShowModal(false); resetForm(); }} className="px-4 py-2 border rounded-lg hover:bg-gray-50 text-sm">إلغاء</button>
              <button onClick={handleSave} className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">{editMode ? 'حفظ التعديلات' : 'إضافة'}</button>
            </div>
          </div>
        </div>
      )}

      {/* Detail Modal */}
      {showDetailModal && selectedRole && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-xl shadow-2xl w-full max-w-md" dir="rtl">
            <div className="p-6 border-b flex justify-between items-center">
              <h2 className="text-lg font-bold">تفاصيل الدور</h2>
              <span className={`px-3 py-1 text-xs rounded-full ${statusColors[selectedRole.status]}`}>{statusLabels[selectedRole.status]}</span>
            </div>
            <div className="p-6 space-y-4">
              <div className="flex items-center gap-4">
                <div className="w-14 h-14 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center font-bold text-xl">{selectedRole.name_ar.charAt(0)}</div>
                <div><p className="text-lg font-bold">{selectedRole.name_ar}</p><p className="text-sm text-gray-500" dir="ltr">{selectedRole.name_en}</p></div>
              </div>
              <div className="bg-gray-50 p-3 rounded-lg"><p className="text-xs text-gray-500 mb-1">الوصف</p><p className="text-sm">{selectedRole.description_ar}</p></div>
              <div className="bg-gray-50 p-3 rounded-lg">
                <p className="text-xs text-gray-500 mb-2">الصلاحيات</p>
                <div className="flex flex-wrap gap-1">{selectedRole.permissions.map(p => <span key={p} className="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded">{moduleLabels[p]}</span>)}</div>
              </div>
              <div className="grid grid-cols-2 gap-3 text-sm">
                <div className="bg-gray-50 p-3 rounded-lg"><p className="text-xs text-gray-500">عدد المستخدمين</p><p className="font-medium">{selectedRole.users_count}</p></div>
                <div className="bg-gray-50 p-3 rounded-lg"><p className="text-xs text-gray-500">تاريخ الإنشاء</p><p className="font-medium">{selectedRole.created_at}</p></div>
              </div>
            </div>
            <div className="p-4 border-t flex justify-end gap-3">
              <button onClick={() => setShowDetailModal(false)} className="px-4 py-2 border rounded-lg hover:bg-gray-50 text-sm">إغلاق</button>
              <button onClick={() => { setShowDetailModal(false); openEdit(selectedRole); }} className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">تعديل</button>
            </div>
          </div>
        </div>
      )}

      {/* Users in Role Modal */}
      {showUsersModal && selectedRole && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-xl shadow-2xl w-full max-w-md" dir="rtl">
            <div className="p-6 border-b">
              <h2 className="text-lg font-bold">مستخدمو دور: {selectedRole.name_ar}</h2>
              <p className="text-sm text-gray-500 mt-1">{selectedRole.users_count} مستخدم</p>
            </div>
            <div className="p-6 space-y-3 max-h-80 overflow-y-auto">
              {(mockRoleUsers[selectedRole.id] || []).map((user, i) => (
                <div key={i} className="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                  <div className="w-9 h-9 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center font-bold text-sm">{user.name.charAt(0)}</div>
                  <div><p className="font-medium text-sm">{user.name}</p><p className="text-xs text-gray-500" dir="ltr">{user.email}</p></div>
                </div>
              ))}
              {(!mockRoleUsers[selectedRole.id] || mockRoleUsers[selectedRole.id].length === 0) && (
                <p className="text-center text-gray-500 py-4">لا يوجد مستخدمون في هذا الدور</p>
              )}
            </div>
            <div className="p-4 border-t flex justify-end">
              <button onClick={() => setShowUsersModal(false)} className="px-4 py-2 border rounded-lg hover:bg-gray-50 text-sm">إغلاق</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
