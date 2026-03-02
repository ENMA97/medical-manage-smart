import { useState, useRef } from 'react';
import api from '../services/api';
import toast from 'react-hot-toast';

export default function ImportEmployees() {
  const [file, setFile] = useState(null);
  const [importType, setImportType] = useState('all');
  const [uploading, setUploading] = useState(false);
  const [results, setResults] = useState(null);
  const fileInputRef = useRef(null);

  function handleFileChange(e) {
    const selected = e.target.files[0];
    if (selected) {
      const validTypes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel',
        'text/csv',
      ];
      if (!validTypes.includes(selected.type) && !selected.name.match(/\.(xlsx|xls|csv)$/i)) {
        toast.error('يرجى اختيار ملف Excel (.xlsx, .xls) أو CSV');
        return;
      }
      setFile(selected);
      setResults(null);
    }
  }

  async function handleUpload(e) {
    e.preventDefault();
    if (!file) {
      toast.error('يرجى اختيار ملف أولاً');
      return;
    }

    setUploading(true);
    setResults(null);

    const formData = new FormData();
    formData.append('file', file);
    formData.append('type', importType);

    try {
      const { data } = await api.post('/import/employees', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });

      setResults(data.data);
      toast.success(data.message);
    } catch (error) {
      const msg = error.response?.data?.message || error.response?.data?.error || 'فشل في استيراد الملف';
      toast.error(msg);
    } finally {
      setUploading(false);
    }
  }

  async function downloadTemplate() {
    try {
      const response = await api.get('/import/template', { responseType: 'blob' });
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', 'employee_import_template.xlsx');
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
      toast.success('تم تحميل القالب');
    } catch {
      toast.error('فشل في تحميل القالب');
    }
  }

  function resetForm() {
    setFile(null);
    setResults(null);
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  }

  return (
    <div>
      <div className="mb-8">
        <h1 className="text-2xl font-bold text-gray-800">استيراد بيانات الموظفين</h1>
        <p className="text-gray-500 mt-1">رفع ملف Excel لاستيراد بيانات الموظفين والعقود والإجازات</p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Upload Section */}
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <h2 className="text-lg font-semibold text-gray-800 mb-4">رفع الملف</h2>

          <form onSubmit={handleUpload} className="space-y-5">
            {/* File Drop Zone */}
            <div
              onClick={() => fileInputRef.current?.click()}
              className="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-all"
            >
              <input
                ref={fileInputRef}
                type="file"
                onChange={handleFileChange}
                accept=".xlsx,.xls,.csv"
                className="hidden"
              />
              {file ? (
                <div className="space-y-2">
                  <div className="inline-flex items-center justify-center w-12 h-12 rounded-full bg-green-100">
                    <svg className="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                  <p className="text-sm font-medium text-gray-800">{file.name}</p>
                  <p className="text-xs text-gray-500">{(file.size / 1024).toFixed(1)} KB</p>
                </div>
              ) : (
                <div className="space-y-2">
                  <div className="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100">
                    <svg className="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                  </div>
                  <p className="text-sm font-medium text-gray-600">اضغط لاختيار ملف أو اسحبه هنا</p>
                  <p className="text-xs text-gray-400">xlsx, xls, csv — حد أقصى 10MB</p>
                </div>
              )}
            </div>

            {/* Import Type */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">نوع الاستيراد</label>
              <div className="grid grid-cols-3 gap-2">
                {[
                  { value: 'all', label: 'الكل' },
                  { value: 'employees', label: 'الموظفون' },
                  { value: 'tamheer', label: 'تمهير' },
                ].map((opt) => (
                  <button
                    key={opt.value}
                    type="button"
                    onClick={() => setImportType(opt.value)}
                    className={`py-2 px-3 rounded-lg text-sm font-medium transition-all ${
                      importType === opt.value
                        ? 'bg-blue-600 text-white'
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                    }`}
                  >
                    {opt.label}
                  </button>
                ))}
              </div>
            </div>

            {/* Actions */}
            <div className="flex gap-3">
              <button
                type="submit"
                disabled={!file || uploading}
                className="flex-1 py-3 px-4 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 text-white font-medium rounded-xl transition-colors flex items-center justify-center gap-2"
              >
                {uploading ? (
                  <>
                    <svg className="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                      <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                      <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                    جاري الاستيراد...
                  </>
                ) : (
                  <>
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    استيراد
                  </>
                )}
              </button>
              {file && (
                <button
                  type="button"
                  onClick={resetForm}
                  className="py-3 px-4 bg-gray-100 hover:bg-gray-200 text-gray-600 font-medium rounded-xl transition-colors"
                >
                  إعادة
                </button>
              )}
            </div>
          </form>

          {/* Download Template */}
          <div className="mt-4 pt-4 border-t border-gray-100">
            <button
              onClick={downloadTemplate}
              className="w-full py-2 px-4 text-sm text-blue-600 hover:bg-blue-50 rounded-lg transition-colors flex items-center justify-center gap-2"
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
              تحميل قالب فارغ
            </button>
          </div>
        </div>

        {/* Instructions & Results */}
        <div className="space-y-6">
          {/* Results */}
          {results && (
            <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
              <h2 className="text-lg font-semibold text-gray-800 mb-4">نتائج الاستيراد</h2>

              <div className="grid grid-cols-2 gap-4 mb-4">
                <ResultStat label="تم إضافتهم" value={results.summary.imported} color="green" />
                <ResultStat label="تم تحديثهم" value={results.summary.updated} color="blue" />
                <ResultStat label="تم تخطيهم" value={results.summary.skipped} color="yellow" />
                <ResultStat label="أخطاء" value={results.summary.errors} color="red" />
              </div>

              {/* Error Details */}
              {results.details && Object.entries(results.details).map(([key, detail]) => (
                detail.errors && detail.errors.length > 0 && (
                  <div key={key} className="mt-4">
                    <h3 className="text-sm font-medium text-red-600 mb-2">
                      أخطاء {key === 'employees' ? 'الموظفين' : 'التمهير'}:
                    </h3>
                    <div className="bg-red-50 rounded-lg p-3 max-h-40 overflow-y-auto">
                      {detail.errors.map((err, i) => (
                        <p key={i} className="text-xs text-red-700 mb-1">{err}</p>
                      ))}
                    </div>
                  </div>
                )
              ))}
            </div>
          )}

          {/* Instructions */}
          <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 className="text-lg font-semibold text-gray-800 mb-4">تعليمات الاستيراد</h2>
            <div className="space-y-3 text-sm text-gray-600">
              <Instruction
                number="1"
                text="حمّل القالب الفارغ أو استخدم ملف Excel الحالي"
              />
              <Instruction
                number="2"
                text="تأكد من وجود ترويسة الأعمدة في الصف الأول"
              />
              <Instruction
                number="3"
                text="الأعمدة المطلوبة: اسم الموظف، الرقم الوظيفي"
              />
              <Instruction
                number="4"
                text="التواريخ بصيغة: يوم/شهر/سنة (مثل 15/03/2024)"
              />
              <Instruction
                number="5"
                text="الورقة الأولى: الموظفون — الورقة الثانية: متدربات تمهير"
              />
            </div>

            <div className="mt-4 p-3 bg-amber-50 rounded-lg">
              <p className="text-xs text-amber-700">
                <strong>ملاحظة:</strong> إذا كان الرقم الوظيفي موجوداً مسبقاً، سيتم تحديث بيانات الموظف بدلاً من إنشاء سجل جديد.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

function ResultStat({ label, value, color }) {
  const colors = {
    green: 'bg-green-50 text-green-700',
    blue: 'bg-blue-50 text-blue-700',
    yellow: 'bg-yellow-50 text-yellow-700',
    red: 'bg-red-50 text-red-700',
  };

  return (
    <div className={`rounded-xl p-3 ${colors[color]}`}>
      <p className="text-2xl font-bold">{value}</p>
      <p className="text-xs mt-1">{label}</p>
    </div>
  );
}

function Instruction({ number, text }) {
  return (
    <div className="flex gap-3 items-start">
      <span className="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">
        {number}
      </span>
      <p>{text}</p>
    </div>
  );
}
