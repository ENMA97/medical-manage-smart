import React, { useState, useEffect } from 'react';

const mockUsers = [
  { id: 1, username: 'admin', name_ar: 'مدير النظام', name_en: 'System Admin', email: 'admin@medical.sa', phone: '0501234567', role: 'admin', department: 'الإدارة العامة', status: 'active', last_login: '2026-02-15 09:30', created_at: '2024-01-01' },
  { id: 2, username: 'hr_manager', name_ar: 'فهد العمري', name_en: 'Fahd Al-Omari', email: 'fahd@medical.sa', phone: '0509876543', role: 'hr_manager', department: 'الموارد البشرية', status: 'active', last_login: '2026-02-15 08:45', created_at: '2024-03-15' },
  { id: 3, username: 'finance_mgr', name_ar: 'نوال الحربي', name_en: 'Nawal Al-Harbi', email: 'nawal@medical.sa', phone: '0551234567', role: 'finance_manager', department: 'المالية', status: 'active', last_login: '2026-02-14 16:20', created_at: '2024-02-10' },
  { id: 4, username: 'dr_ahmed', name_ar: 'د. أحمد الشمري', name_en: 'Dr. Ahmed Al-Shamri', email: 'ahmed@medical.sa', phone: '0561234567', role: 'doctor', department: 'الباطنية', status: 'active', last_login: '2026-02-15 07:00', created_at: '2024-06-01' },
  { id: 5, username: 'nurse_sara', name_ar: 'سارة القحطاني', name_en: 'Sara Al-Qahtani', email: 'sara@medical.sa', phone: '0571234567', role: 'nurse', department: 'التمريض', status: 'active', last_login: '2026-02-15 06:50', created_at: '2024-07-20' },
  { id: 6, username: 'pharm_khalid', name_ar: 'خالد الغامدي', name_en: 'Khalid Al-Ghamdi', email: 'khalid@medical.sa', phone: '0581234567', role: 'pharmacist', department: 'الصيدلية', status: 'inactive', last_login: '2026-01-20 14:00', created_at: '2024-08-05' },
  { id: 7, username: 'lab_tech', name_ar: 'مريم العتيبي', name_en: 'Mariam Al-Otaibi', email: 'mariam@medical.sa', phone: '0591234567', role: 'lab_technician', department: 'المختبرات', status: 'active', last_login: '2026-02-15 08:00', created_at: '2024-09-10' },
  { id: 8, username: 'receptionist', name_ar: 'عبدالله الدوسري', name_en: 'Abdullah Al-Dosari', email: 'abdullah@medical.sa', phone: '0521234567', role: 'receptionist', department: 'الاستقبال', status: 'locked', last_login: '2026-02-10 09:00', created_at: '2025-01-15' },
];

const roleLabels = {
  admin: 'مدير النظام', hr_manager: 'مدير الموارد البشرية', finance_manager: 'مدير المالية',
  doctor: 'طبيب', nurse: 'ممرض/ة', pharmacist: 'صيدلي', lab_technician: 'فني مختبر', receptionist: 'موظف استقبال'
};
const roleColors = {
  admin: 'bg-red-100 text-red-700', hr_manager: 'bg-blue-100 text-blue-700', finance_manager: 'bg-green-100 text-green-700',
  doctor: 'bg-purple-100 text-purple-700', nurse: 'bg-pink-100 text-pink-700', pharmacist: 'bg-yellow-100 text-yellow-700',
  lab_technician: 'bg-indigo-100 text-indigo-700', receptionist: 'bg-gray-100 text-gray-700'
};
const statusLabels = { active: 'نشط', inactive: 'غير نشط', locked: 'مقفل' };
const statusColors = { active: 'bg-green-100 text-green-700', inactive: 'bg-gray-100 text-gray-700', locked: 'bg-red-100 text-red-700' };

export default function UsersPage() {
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterRole, setFilterRole] = useState('');
  const [filterStatus, setFilterStatus] = useState('');
  const [showModal, setShowModal] = useState(false);
  const [showDetailModal, setShowDetailModal] = useState(false);
  const [selectedUser, setSelectedUser] = useState(null);
  const [editMode, setEditMode] = useState(false);
  const [formData, setFormData] = useState({ username: '', name_ar: '', name_en: '', email: '', phone: '', role: 'receptionist', department: '', password: '' });

  useEffect(() => {
    setTimeout(() => { setUsers(mockUsers); setLoading(false); }, 400);
  }, []);

  const filtered = users.filter(u => {
    if (filterRole && u.role !== filterRole) return false;
    if (filterStatus && u.status !== filterStatus) return false;
    if (searchTerm && !u.name_ar.includes(searchTerm) && !u.username.includes(searchTerm) && !u.email.includes(searchTerm)) return false;
    return true;
  });

  const handleSave = () => {
    if (!formData.name_ar || !formData.email || !formData.username) { alert('الرجاء ملء الحقول المطلوبة'); return; }
    if (editMode && selectedUser) {
      setUsers(prev => prev.map(u => u.id === selectedUser.id ? { ...u, ...formData } : u));
    } else {
      setUsers(prev => [...prev, { id: Math.max(...prev.map(u => u.id)) + 1, ...formData, status: 'active', last_login: '-', created_at: new Date().toISOString().split('T')[0] }]);
    }
    setShowModal(false);
    resetForm();
  };

  const resetForm = () => {
    setFormData({ username: '', name_ar: '', name_en: '', email: '', phone: '', role: 'receptionist', department: '', password: '' });
    setEditMode(false);
    setSelectedUser(null);
  };

  const openEdit = (user) => {
    setFormData({ username: user.username, name_ar: user.name_ar, name_en: user.name_en, email: user.email, phone: user.phone, role: user.role, department: user.department, password: '' });
    setSelectedUser(user);
    setEditMode(true);
    setShowModal(true);
  };

  const toggleStatus = (id) => {
    setUsers(prev => prev.map(u => u.id === id ? { ...u, status: u.status === 'active' ? 'inactive' : 'active' } : u));
  };

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div></div>;

  return (
    <div className="space-y-6" dir="rtl">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">إدارة المستخدمين</h1>
          <p className="text-gray-600 mt-1">إدارة حسابات المستخدمين والصلاحيات</p>
        </div>
        <button onClick={() => { resetForm(); setShowModal(true); }} className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">+ إضافة مستخدم</button>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div className="bg-white rounded-lg shadow p-4"><p className="text-xs text-gray-500">إجمالي المستخدمين</p><p className="text-2xl font-bold mt-1">{users.length}</p></div>
        <div className="bg-white rounded-lg shadow p-4"><p className="text-xs text-gray-500">نشط</p><p className="text-2xl font-bold text-green-600 mt-1">{users.filter(u => u.status === 'active').length}</p></div>
        <div className="bg-white rounded-lg shadow p-4"><p className="text-xs text-gray-500">غير نشط</p><p className="text-2xl font-bold text-gray-500 mt-1">{users.filter(u => u.status === 'inactive').length}</p></div>
        <div className="bg-white rounded-lg shadow p-4"><p className="text-xs text-gray-500">مقفل</p><p className="text-2xl font-bold text-red-600 mt-1">{users.filter(u => u.status === 'locked').length}</p></div>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-lg shadow p-4 flex flex-wrap gap-4">
        <input type="text" placeholder="بحث بالاسم أو البريد..." value={searchTerm} onChange={e => setSearchTerm(e.target.value)} className="border rounded-lg px-4 py-2 text-sm w-64" />
        <select value={filterRole} onChange={e => setFilterRole(e.target.value)} className="border rounded-lg px-4 py-2 text-sm">
          <option value="">جميع الأدوار</option>
          {Object.entries(roleLabels).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
        </select>
        <select value={filterStatus} onChange={e => setFilterStatus(e.target.value)} className="border rounded-lg px-4 py-2 text-sm">
          <option value="">جميع الحالات</option>
          {Object.entries(statusLabels).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
        </select>
      </div>

      {/* Table */}
      <div className="bg-white rounded-lg shadow overflow-hidden">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">المستخدم</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">البريد الإلكتروني</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الدور</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">القسم</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">آخر دخول</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الإجراءات</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {filtered.map(user => (
                <tr key={user.id} className="hover:bg-gray-50">
                  <td className="px-4 py-4 text-sm">
                    <div className="flex items-center gap-3">
                      <div className="w-9 h-9 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center font-bold text-sm">
                        {user.name_ar.charAt(0)}
                      </div>
                      <div><div className="font-medium text-gray-900">{user.name_ar}</div><div className="text-xs text-gray-500">@{user.username}</div></div>
                    </div>
                  </td>
                  <td className="px-4 py-4 text-sm text-gray-500">{user.email}</td>
                  <td className="px-4 py-4"><span className={`px-2 py-1 text-xs rounded-full ${roleColors[user.role]}`}>{roleLabels[user.role]}</span></td>
                  <td className="px-4 py-4 text-sm text-gray-500">{user.department}</td>
                  <td className="px-4 py-4"><span className={`px-2 py-1 text-xs rounded-full ${statusColors[user.status]}`}>{statusLabels[user.status]}</span></td>
                  <td className="px-4 py-4 text-xs text-gray-500">{user.last_login}</td>
                  <td className="px-4 py-4 text-sm">
                    <div className="flex gap-2">
                      <button onClick={() => { setSelectedUser(user); setShowDetailModal(true); }} className="text-blue-600 hover:text-blue-900">عرض</button>
                      <button onClick={() => openEdit(user)} className="text-yellow-600 hover:text-yellow-900">تعديل</button>
                      <button onClick={() => toggleStatus(user.id)} className="text-gray-600 hover:text-gray-900">
                        {user.status === 'active' ? 'تعطيل' : 'تفعيل'}
                      </button>
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
            <div className="p-6 border-b"><h2 className="text-lg font-bold">{editMode ? 'تعديل مستخدم' : 'إضافة مستخدم جديد'}</h2></div>
            <div className="p-6 space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div><label className="block text-sm font-medium text-gray-700 mb-1">الاسم (عربي) *</label>
                  <input value={formData.name_ar} onChange={e => setFormData({...formData, name_ar: e.target.value})} className="w-full border rounded-lg px-3 py-2 text-sm" /></div>
                <div><label className="block text-sm font-medium text-gray-700 mb-1">الاسم (English)</label>
                  <input value={formData.name_en} onChange={e => setFormData({...formData, name_en: e.target.value})} className="w-full border rounded-lg px-3 py-2 text-sm" dir="ltr" /></div>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div><label className="block text-sm font-medium text-gray-700 mb-1">اسم المستخدم *</label>
                  <input value={formData.username} onChange={e => setFormData({...formData, username: e.target.value})} className="w-full border rounded-lg px-3 py-2 text-sm" dir="ltr" /></div>
                <div><label className="block text-sm font-medium text-gray-700 mb-1">البريد الإلكتروني *</label>
                  <input type="email" value={formData.email} onChange={e => setFormData({...formData, email: e.target.value})} className="w-full border rounded-lg px-3 py-2 text-sm" dir="ltr" /></div>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div><label className="block text-sm font-medium text-gray-700 mb-1">الهاتف</label>
                  <input value={formData.phone} onChange={e => setFormData({...formData, phone: e.target.value})} className="w-full border rounded-lg px-3 py-2 text-sm" dir="ltr" /></div>
                <div><label className="block text-sm font-medium text-gray-700 mb-1">كلمة المرور {!editMode && '*'}</label>
                  <input type="password" value={formData.password} onChange={e => setFormData({...formData, password: e.target.value})} className="w-full border rounded-lg px-3 py-2 text-sm" dir="ltr" placeholder={editMode ? 'اتركها فارغة للإبقاء' : ''} /></div>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div><label className="block text-sm font-medium text-gray-700 mb-1">الدور *</label>
                  <select value={formData.role} onChange={e => setFormData({...formData, role: e.target.value})} className="w-full border rounded-lg px-3 py-2 text-sm">
                    {Object.entries(roleLabels).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                  </select></div>
                <div><label className="block text-sm font-medium text-gray-700 mb-1">القسم</label>
                  <input value={formData.department} onChange={e => setFormData({...formData, department: e.target.value})} className="w-full border rounded-lg px-3 py-2 text-sm" /></div>
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
      {showDetailModal && selectedUser && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-xl shadow-2xl w-full max-w-md" dir="rtl">
            <div className="p-6 border-b flex justify-between items-center">
              <h2 className="text-lg font-bold">تفاصيل المستخدم</h2>
              <span className={`px-3 py-1 text-xs rounded-full ${statusColors[selectedUser.status]}`}>{statusLabels[selectedUser.status]}</span>
            </div>
            <div className="p-6 space-y-4">
              <div className="flex items-center gap-4">
                <div className="w-16 h-16 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center font-bold text-2xl">{selectedUser.name_ar.charAt(0)}</div>
                <div><p className="text-lg font-bold">{selectedUser.name_ar}</p><p className="text-sm text-gray-500">{selectedUser.name_en}</p><p className="text-xs text-gray-400">@{selectedUser.username}</p></div>
              </div>
              <div className="grid grid-cols-2 gap-3 text-sm">
                <div className="bg-gray-50 p-3 rounded-lg"><p className="text-xs text-gray-500">البريد</p><p className="font-medium">{selectedUser.email}</p></div>
                <div className="bg-gray-50 p-3 rounded-lg"><p className="text-xs text-gray-500">الهاتف</p><p className="font-medium">{selectedUser.phone}</p></div>
                <div className="bg-gray-50 p-3 rounded-lg"><p className="text-xs text-gray-500">الدور</p><p><span className={`px-2 py-0.5 text-xs rounded-full ${roleColors[selectedUser.role]}`}>{roleLabels[selectedUser.role]}</span></p></div>
                <div className="bg-gray-50 p-3 rounded-lg"><p className="text-xs text-gray-500">القسم</p><p className="font-medium">{selectedUser.department}</p></div>
                <div className="bg-gray-50 p-3 rounded-lg"><p className="text-xs text-gray-500">آخر دخول</p><p className="font-medium">{selectedUser.last_login}</p></div>
                <div className="bg-gray-50 p-3 rounded-lg"><p className="text-xs text-gray-500">تاريخ الإنشاء</p><p className="font-medium">{selectedUser.created_at}</p></div>
              </div>
            </div>
            <div className="p-4 border-t flex justify-end gap-3">
              <button onClick={() => setShowDetailModal(false)} className="px-4 py-2 border rounded-lg hover:bg-gray-50 text-sm">إغلاق</button>
              <button onClick={() => { setShowDetailModal(false); openEdit(selectedUser); }} className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">تعديل</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
