import { useState, useEffect, useCallback } from 'react';
import toast from 'react-hot-toast';
import settingService from '../services/settingService';

export default function Settings() {
  const [settings, setSettings] = useState([]);
  const [loading, setLoading] = useState(true);
  const [editingId, setEditingId] = useState(null);
  const [editValue, setEditValue] = useState('');

  const fetchSettings = useCallback(async () => {
    setLoading(true);
    try {
      const { data } = await settingService.getAll();
      setSettings(data.data);
    } catch {
      toast.error('حدث خطأ في تحميل الإعدادات');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { fetchSettings(); }, [fetchSettings]);

  async function handleSave(id) {
    try {
      await settingService.update(id, { value: editValue });
      toast.success('تم حفظ الإعداد');
      setEditingId(null);
      fetchSettings();
    } catch (err) {
      toast.error(err.response?.data?.message || 'حدث خطأ');
    }
  }

  return (
    <div className="space-y-4">
      <h1 className="text-xl font-bold text-gray-800">إعدادات النظام</h1>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center py-16"><div className="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin" /></div>
        ) : settings.length === 0 ? (
          <div className="text-center py-16 text-gray-500">لا يوجد إعدادات</div>
        ) : (
          <div className="divide-y divide-gray-50">
            {settings.map((setting) => (
              <div key={setting.id} className="px-4 py-3">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="font-medium text-gray-800 text-sm">{setting.label || setting.key}</p>
                    {setting.description && <p className="text-xs text-gray-500 mt-0.5">{setting.description}</p>}
                  </div>
                  {editingId === setting.id ? (
                    <div className="flex items-center gap-2">
                      <input
                        type="text" value={editValue} onChange={(e) => setEditValue(e.target.value)} autoFocus
                        className="w-48 px-2 py-1 border border-gray-200 rounded text-sm"
                      />
                      <button onClick={() => handleSave(setting.id)} className="text-xs text-green-600 font-medium">حفظ</button>
                      <button onClick={() => setEditingId(null)} className="text-xs text-gray-500 font-medium">إلغاء</button>
                    </div>
                  ) : (
                    <div className="flex items-center gap-2">
                      <span className="text-sm text-gray-600 font-mono">{setting.value}</span>
                      <button onClick={() => { setEditingId(setting.id); setEditValue(setting.value); }} className="text-xs text-blue-600 font-medium">تعديل</button>
                    </div>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
