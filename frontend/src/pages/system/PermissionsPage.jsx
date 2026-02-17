import React, { useState, useEffect } from 'react';

const modules = [
  { key: 'hr', label: 'الموارد البشرية' },
  { key: 'inventory', label: 'المخزون' },
  { key: 'roster', label: 'الجداول' },
  { key: 'finance', label: 'المالية' },
  { key: 'payroll', label: 'الرواتب' },
  { key: 'system', label: 'النظام' },
  { key: 'leaves', label: 'الإجازات' },
];

const permissionTypes = [
  { key: 'view', label: 'عرض' },
  { key: 'create', label: 'إنشاء' },
  { key: 'edit', label: 'تعديل' },
  { key: 'delete', label: 'حذف' },
  { key: 'approve', label: 'موافقة' },
  { key: 'export', label: 'تصدير' },
];

const roles = [
  { key: 'admin', label: 'مدير النظام' },
  { key: 'hr_manager', label: 'مدير الموارد البشرية' },
  { key: 'finance_manager', label: 'مدير المالية' },
  { key: 'doctor', label: 'طبيب' },
  { key: 'nurse', label: 'ممرض/ة' },
  { key: 'pharmacist', label: 'صيدلي' },
  { key: 'lab_technician', label: 'فني مختبر' },
  { key: 'receptionist', label: 'موظف استقبال' },
];

const buildInitialMatrix = () => {
  const matrix = {};
  roles.forEach(role => {
    matrix[role.key] = {};
    modules.forEach(mod => {
      matrix[role.key][mod.key] = {};
      permissionTypes.forEach(perm => {
        if (role.key === 'admin') {
          matrix[role.key][mod.key][perm.key] = true;
        } else if (role.key === 'hr_manager') {
          matrix[role.key][mod.key][perm.key] = ['hr', 'roster', 'leaves'].includes(mod.key);
        } else if (role.key === 'finance_manager') {
          matrix[role.key][mod.key][perm.key] = ['finance', 'payroll'].includes(mod.key);
        } else if (role.key === 'doctor') {
          if (['roster', 'leaves'].includes(mod.key)) {
            matrix[role.key][mod.key][perm.key] = ['view', 'create'].includes(perm.key);
          } else {
            matrix[role.key][mod.key][perm.key] = false;
          }
        } else if (role.key === 'nurse') {
          if (['roster', 'inventory', 'leaves'].includes(mod.key)) {
            matrix[role.key][mod.key][perm.key] = ['view', 'create'].includes(perm.key);
          } else {
            matrix[role.key][mod.key][perm.key] = false;
          }
        } else if (role.key === 'pharmacist') {
          if (mod.key === 'inventory') {
            matrix[role.key][mod.key][perm.key] = ['view', 'create', 'edit'].includes(perm.key);
          } else if (mod.key === 'leaves') {
            matrix[role.key][mod.key][perm.key] = ['view', 'create'].includes(perm.key);
          } else {
            matrix[role.key][mod.key][perm.key] = false;
          }
        } else if (role.key === 'lab_technician') {
          if (mod.key === 'inventory') {
            matrix[role.key][mod.key][perm.key] = ['view'].includes(perm.key);
          } else if (mod.key === 'leaves') {
            matrix[role.key][mod.key][perm.key] = ['view', 'create'].includes(perm.key);
          } else {
            matrix[role.key][mod.key][perm.key] = false;
          }
        } else if (role.key === 'receptionist') {
          if (mod.key === 'leaves') {
            matrix[role.key][mod.key][perm.key] = ['view', 'create'].includes(perm.key);
          } else {
            matrix[role.key][mod.key][perm.key] = false;
          }
        } else {
          matrix[role.key][mod.key][perm.key] = false;
        }
      });
    });
  });
  return matrix;
};

const roleColors = {
  admin: 'bg-red-100 text-red-700', hr_manager: 'bg-blue-100 text-blue-700', finance_manager: 'bg-green-100 text-green-700',
  doctor: 'bg-purple-100 text-purple-700', nurse: 'bg-pink-100 text-pink-700', pharmacist: 'bg-yellow-100 text-yellow-700',
  lab_technician: 'bg-indigo-100 text-indigo-700', receptionist: 'bg-gray-100 text-gray-700'
};

export default function PermissionsPage() {
  const [matrix, setMatrix] = useState({});
  const [loading, setLoading] = useState(true);
  const [selectedModule, setSelectedModule] = useState('');
  const [compareRoles, setCompareRoles] = useState([]);
  const [showCompare, setShowCompare] = useState(false);
  const [savedMsg, setSavedMsg] = useState('');

  useEffect(() => {
    setTimeout(() => { setMatrix(buildInitialMatrix()); setLoading(false); }, 400);
  }, []);

  const togglePermission = (roleKey, modKey, permKey) => {
    setMatrix(prev => ({
      ...prev,
      [roleKey]: {
        ...prev[roleKey],
        [modKey]: {
          ...prev[roleKey][modKey],
          [permKey]: !prev[roleKey][modKey][permKey]
        }
      }
    }));
  };

  const bulkAssignModule = (roleKey, modKey, value) => {
    setMatrix(prev => {
      const updated = { ...prev };
      updated[roleKey] = { ...updated[roleKey] };
      updated[roleKey][modKey] = {};
      permissionTypes.forEach(p => { updated[roleKey][modKey][p.key] = value; });
      return updated;
    });
  };

  const bulkAssignRole = (roleKey, value) => {
    setMatrix(prev => {
      const updated = { ...prev };
      updated[roleKey] = {};
      modules.forEach(mod => {
        updated[roleKey][mod.key] = {};
        permissionTypes.forEach(p => { updated[roleKey][mod.key][p.key] = value; });
      });
      return updated;
    });
  };

  const getPermCount = (roleKey) => {
    if (!matrix[roleKey]) return 0;
    let count = 0;
    modules.forEach(mod => {
      permissionTypes.forEach(p => {
        if (matrix[roleKey]?.[mod.key]?.[p.key]) count++;
      });
    });
    return count;
  };

  const totalPerms = modules.length * permissionTypes.length;

  const toggleCompareRole = (roleKey) => {
    setCompareRoles(prev => prev.includes(roleKey) ? prev.filter(r => r !== roleKey) : prev.length < 3 ? [...prev, roleKey] : prev);
  };

  const handleSave = () => {
    setSavedMsg('تم حفظ الصلاحيات بنجاح');
    setTimeout(() => setSavedMsg(''), 3000);
  };

  const visibleModules = selectedModule ? modules.filter(m => m.key === selectedModule) : modules;

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div></div>;

  return (
    <div className="space-y-6" dir="rtl">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">إدارة الصلاحيات</h1>
          <p className="text-gray-600 mt-1">مصفوفة صلاحيات الأدوار حسب الوحدات</p>
        </div>
        <div className="flex gap-2">
          <button onClick={() => setShowCompare(!showCompare)} className={`px-4 py-2 border rounded-lg text-sm ${showCompare ? 'bg-blue-50 border-blue-300 text-blue-700' : 'hover:bg-gray-50'}`}>
            مقارنة الأدوار
          </button>
          <button onClick={handleSave} className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">حفظ التغييرات</button>
        </div>
      </div>

      {savedMsg && (
        <div className="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{savedMsg}</div>
      )}

      {/* Stats */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div className="bg-white rounded-lg shadow p-4"><p className="text-xs text-gray-500">إجمالي الأدوار</p><p className="text-2xl font-bold mt-1">{roles.length}</p></div>
        <div className="bg-white rounded-lg shadow p-4"><p className="text-xs text-gray-500">الوحدات</p><p className="text-2xl font-bold text-blue-600 mt-1">{modules.length}</p></div>
        <div className="bg-white rounded-lg shadow p-4"><p className="text-xs text-gray-500">أنواع الصلاحيات</p><p className="text-2xl font-bold text-purple-600 mt-1">{permissionTypes.length}</p></div>
        <div className="bg-white rounded-lg shadow p-4"><p className="text-xs text-gray-500">إجمالي التعيينات</p><p className="text-2xl font-bold text-green-600 mt-1">{roles.reduce((s, r) => s + getPermCount(r.key), 0)}</p></div>
      </div>

      {/* Compare Modal */}
      {showCompare && (
        <div className="bg-white rounded-lg shadow p-4">
          <h3 className="font-bold text-sm text-gray-700 mb-3">اختر أدوار للمقارنة (حتى 3)</h3>
          <div className="flex flex-wrap gap-2 mb-4">
            {roles.map(role => (
              <button key={role.key} onClick={() => toggleCompareRole(role.key)}
                className={`px-3 py-1.5 text-xs rounded-full border ${compareRoles.includes(role.key) ? 'bg-blue-600 text-white border-blue-600' : 'hover:bg-gray-50'}`}>
                {role.label}
              </button>
            ))}
          </div>
          {compareRoles.length >= 2 && (
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200 text-sm">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-3 py-2 text-right text-xs font-medium text-gray-500">الوحدة / الصلاحية</th>
                    {compareRoles.map(rk => {
                      const r = roles.find(x => x.key === rk);
                      return <th key={rk} className="px-3 py-2 text-center text-xs font-medium text-gray-500">{r?.label}</th>;
                    })}
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200">
                  {modules.map(mod => (
                    permissionTypes.map((perm, pi) => (
                      <tr key={`${mod.key}-${perm.key}`} className={pi === 0 ? 'border-t-2 border-gray-300' : ''}>
                        <td className="px-3 py-1.5 text-xs">
                          {pi === 0 && <span className="font-bold text-gray-700">{mod.label} - </span>}
                          <span className="text-gray-500">{perm.label}</span>
                        </td>
                        {compareRoles.map(rk => (
                          <td key={rk} className="px-3 py-1.5 text-center">
                            {matrix[rk]?.[mod.key]?.[perm.key] ? (
                              <span className="text-green-600 font-bold">&#10003;</span>
                            ) : (
                              <span className="text-red-400">&#10005;</span>
                            )}
                          </td>
                        ))}
                      </tr>
                    ))
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}

      {/* Filter */}
      <div className="bg-white rounded-lg shadow p-4 flex flex-wrap gap-4">
        <select value={selectedModule} onChange={e => setSelectedModule(e.target.value)} className="border rounded-lg px-4 py-2 text-sm">
          <option value="">جميع الوحدات</option>
          {modules.map(m => <option key={m.key} value={m.key}>{m.label}</option>)}
        </select>
        <div className="flex-1" />
        <div className="flex gap-2 text-xs text-gray-500 items-center">
          <span className="text-green-600 font-bold">&#10003;</span> = مفعّل
          <span className="mx-2">|</span>
          <span className="text-gray-300 font-bold">&#9744;</span> = معطّل
        </div>
      </div>

      {/* Permission Matrix */}
      {visibleModules.map(mod => (
        <div key={mod.key} className="bg-white rounded-lg shadow overflow-hidden">
          <div className="px-4 py-3 border-b bg-gray-50 flex justify-between items-center">
            <h3 className="font-bold text-sm text-gray-700">وحدة {mod.label}</h3>
          </div>
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 w-48">الدور</th>
                  {permissionTypes.map(p => (
                    <th key={p.key} className="px-3 py-3 text-center text-xs font-medium text-gray-500">{p.label}</th>
                  ))}
                  <th className="px-3 py-3 text-center text-xs font-medium text-gray-500">الكل</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {roles.map(role => {
                  const allChecked = permissionTypes.every(p => matrix[role.key]?.[mod.key]?.[p.key]);
                  return (
                    <tr key={role.key} className="hover:bg-gray-50">
                      <td className="px-4 py-3 text-sm">
                        <span className={`px-2 py-1 text-xs rounded-full ${roleColors[role.key]}`}>{role.label}</span>
                      </td>
                      {permissionTypes.map(perm => (
                        <td key={perm.key} className="px-3 py-3 text-center">
                          <input type="checkbox" checked={!!matrix[role.key]?.[mod.key]?.[perm.key]} onChange={() => togglePermission(role.key, mod.key, perm.key)}
                            className="w-4 h-4 rounded border-gray-300 text-blue-600 cursor-pointer" />
                        </td>
                      ))}
                      <td className="px-3 py-3 text-center">
                        <input type="checkbox" checked={allChecked} onChange={() => bulkAssignModule(role.key, mod.key, !allChecked)}
                          className="w-4 h-4 rounded border-gray-300 text-blue-600 cursor-pointer" />
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      ))}

      {/* Role Summary */}
      <div className="bg-white rounded-lg shadow overflow-hidden">
        <div className="px-4 py-3 border-b bg-gray-50"><h3 className="font-bold text-sm text-gray-700">ملخص الصلاحيات حسب الدور</h3></div>
        <div className="p-4 grid grid-cols-2 md:grid-cols-4 gap-3">
          {roles.map(role => {
            const count = getPermCount(role.key);
            const pct = Math.round((count / totalPerms) * 100);
            return (
              <div key={role.key} className="border rounded-lg p-3">
                <div className="flex justify-between items-center mb-2">
                  <span className={`px-2 py-0.5 text-xs rounded-full ${roleColors[role.key]}`}>{role.label}</span>
                  <span className="text-xs text-gray-500">{count}/{totalPerms}</span>
                </div>
                <div className="w-full bg-gray-200 rounded-full h-2">
                  <div className="bg-blue-600 h-2 rounded-full" style={{ width: `${pct}%` }}></div>
                </div>
                <div className="flex justify-between mt-1">
                  <span className="text-xs text-gray-500">{pct}%</span>
                  <button onClick={() => bulkAssignRole(role.key, count < totalPerms)} className="text-xs text-blue-600 hover:text-blue-800">
                    {count === totalPerms ? 'إزالة الكل' : 'تعيين الكل'}
                  </button>
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
}
