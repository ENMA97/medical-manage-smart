import React, { useState, useEffect, useMemo } from 'react';
import { shiftSwapRequestsApi } from '../../services/rosterApi';
import { Button, LoadingSpinner, Modal, EmptyState } from '../../components/ui';

/**
 * صفحة طلبات تبديل الورديات
 * Shift Swap Requests Page - Handle shift exchange between employees
 */
export default function ShiftSwapsPage() {
  // State
  const [requests, setRequests] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [activeTab, setActiveTab] = useState('all'); // all, my_requests, pending_for_me, pending_approval
  const [filterStatus, setFilterStatus] = useState('all');
  const [searchQuery, setSearchQuery] = useState('');

  // Modal states
  const [showNewRequestModal, setShowNewRequestModal] = useState(false);
  const [showDetailsModal, setShowDetailsModal] = useState(false);
  const [showActionModal, setShowActionModal] = useState(false);
  const [selectedRequest, setSelectedRequest] = useState(null);
  const [actionType, setActionType] = useState(null); // accept, reject, approve, manager_reject, cancel
  const [actionReason, setActionReason] = useState('');
  const [saving, setSaving] = useState(false);

  // New request form
  const [newRequest, setNewRequest] = useState({
    target_employee_id: '',
    my_shift_date: '',
    my_shift_id: '',
    target_shift_date: '',
    target_shift_id: '',
    reason: ''
  });

  // Mock employees
  const employees = [
    { id: 1, name: 'أحمد محمد', employee_number: 'EMP001', department: 'التمريض' },
    { id: 2, name: 'فاطمة علي', employee_number: 'EMP002', department: 'التمريض' },
    { id: 3, name: 'محمد خالد', employee_number: 'EMP003', department: 'التمريض' },
    { id: 4, name: 'سارة أحمد', employee_number: 'EMP004', department: 'التمريض' },
    { id: 5, name: 'عبدالله سعيد', employee_number: 'EMP005', department: 'التمريض' }
  ];

  // Mock shifts
  const shifts = [
    { id: 1, name: 'صباحي (فترتين)', time: '9:00-12:00 + 17:00-22:00' },
    { id: 2, name: 'مسائي', time: '16:00-00:00' },
    { id: 3, name: 'ليلي', time: '00:00-09:00' },
    { id: 4, name: 'صباحي', time: '09:00-17:00' }
  ];

  // Current user (mock)
  const currentUser = { id: 1, name: 'أحمد محمد', isManager: true };

  // Mock swap requests
  const mockRequests = [
    {
      id: 1,
      requester_id: 1,
      requester_name: 'أحمد محمد',
      requester_number: 'EMP001',
      target_id: 2,
      target_name: 'فاطمة علي',
      target_number: 'EMP002',
      my_shift_date: '2025-01-20',
      my_shift_name: 'صباحي (فترتين)',
      my_shift_time: '9:00-12:00 + 17:00-22:00',
      target_shift_date: '2025-01-21',
      target_shift_name: 'مسائي',
      target_shift_time: '16:00-00:00',
      reason: 'موعد طبي',
      status: 'pending_target',
      created_at: '2025-01-15T10:30:00',
      target_response: null,
      target_response_date: null,
      manager_response: null,
      manager_response_date: null,
      manager_notes: null
    },
    {
      id: 2,
      requester_id: 3,
      requester_name: 'محمد خالد',
      requester_number: 'EMP003',
      target_id: 1,
      target_name: 'أحمد محمد',
      target_number: 'EMP001',
      my_shift_date: '2025-01-22',
      my_shift_name: 'ليلي',
      my_shift_time: '00:00-09:00',
      target_shift_date: '2025-01-23',
      target_shift_name: 'صباحي',
      target_shift_time: '09:00-17:00',
      reason: 'ظروف عائلية',
      status: 'pending_target',
      created_at: '2025-01-14T14:00:00',
      target_response: null,
      target_response_date: null,
      manager_response: null,
      manager_response_date: null,
      manager_notes: null
    },
    {
      id: 3,
      requester_id: 2,
      requester_name: 'فاطمة علي',
      requester_number: 'EMP002',
      target_id: 4,
      target_name: 'سارة أحمد',
      target_number: 'EMP004',
      my_shift_date: '2025-01-25',
      my_shift_name: 'مسائي',
      my_shift_time: '16:00-00:00',
      target_shift_date: '2025-01-26',
      target_shift_name: 'صباحي',
      target_shift_time: '09:00-17:00',
      reason: 'مناسبة خاصة',
      status: 'pending_manager',
      created_at: '2025-01-13T09:00:00',
      target_response: 'accepted',
      target_response_date: '2025-01-13T15:30:00',
      manager_response: null,
      manager_response_date: null,
      manager_notes: null
    },
    {
      id: 4,
      requester_id: 4,
      requester_name: 'سارة أحمد',
      requester_number: 'EMP004',
      target_id: 5,
      target_name: 'عبدالله سعيد',
      target_number: 'EMP005',
      my_shift_date: '2025-01-18',
      my_shift_name: 'صباحي',
      my_shift_time: '09:00-17:00',
      target_shift_date: '2025-01-19',
      target_shift_name: 'مسائي',
      target_shift_time: '16:00-00:00',
      reason: 'موعد مهم',
      status: 'approved',
      created_at: '2025-01-10T11:00:00',
      target_response: 'accepted',
      target_response_date: '2025-01-10T16:00:00',
      manager_response: 'approved',
      manager_response_date: '2025-01-11T09:00:00',
      manager_notes: 'تمت الموافقة'
    },
    {
      id: 5,
      requester_id: 5,
      requester_name: 'عبدالله سعيد',
      requester_number: 'EMP005',
      target_id: 3,
      target_name: 'محمد خالد',
      target_number: 'EMP003',
      my_shift_date: '2025-01-16',
      my_shift_name: 'مسائي',
      my_shift_time: '16:00-00:00',
      target_shift_date: '2025-01-17',
      target_shift_name: 'ليلي',
      target_shift_time: '00:00-09:00',
      reason: 'دراسة',
      status: 'rejected_by_target',
      created_at: '2025-01-08T08:00:00',
      target_response: 'rejected',
      target_response_date: '2025-01-08T18:00:00',
      target_rejection_reason: 'لدي التزامات في ذلك اليوم',
      manager_response: null,
      manager_response_date: null,
      manager_notes: null
    }
  ];

  // Load requests
  useEffect(() => {
    loadRequests();
  }, []);

  const loadRequests = async () => {
    try {
      setLoading(true);
      setError(null);
      // const response = await shiftSwapRequestsApi.getAll();
      // setRequests(response.data);

      // Using mock data
      setTimeout(() => {
        setRequests(mockRequests);
        setLoading(false);
      }, 500);
    } catch (err) {
      setError('فشل في تحميل طلبات التبديل');
      setLoading(false);
    }
  };

  // Filter requests based on tab and filters
  const filteredRequests = useMemo(() => {
    let filtered = [...requests];

    // Tab filtering
    if (activeTab === 'my_requests') {
      filtered = filtered.filter(r => r.requester_id === currentUser.id);
    } else if (activeTab === 'pending_for_me') {
      filtered = filtered.filter(r =>
        r.target_id === currentUser.id && r.status === 'pending_target'
      );
    } else if (activeTab === 'pending_approval') {
      filtered = filtered.filter(r => r.status === 'pending_manager');
    }

    // Status filter
    if (filterStatus !== 'all') {
      filtered = filtered.filter(r => r.status === filterStatus);
    }

    // Search filter
    if (searchQuery) {
      const query = searchQuery.toLowerCase();
      filtered = filtered.filter(r =>
        r.requester_name.toLowerCase().includes(query) ||
        r.target_name.toLowerCase().includes(query) ||
        r.requester_number.toLowerCase().includes(query) ||
        r.target_number.toLowerCase().includes(query)
      );
    }

    return filtered;
  }, [requests, activeTab, filterStatus, searchQuery, currentUser.id]);

  // Statistics
  const stats = useMemo(() => {
    return {
      total: requests.length,
      pending_target: requests.filter(r => r.status === 'pending_target').length,
      pending_manager: requests.filter(r => r.status === 'pending_manager').length,
      approved: requests.filter(r => r.status === 'approved').length,
      rejected: requests.filter(r =>
        r.status === 'rejected_by_target' || r.status === 'rejected_by_manager'
      ).length,
      my_pending: requests.filter(r =>
        r.target_id === currentUser.id && r.status === 'pending_target'
      ).length
    };
  }, [requests, currentUser.id]);

  // Handle new request submission
  const handleSubmitRequest = async (e) => {
    e.preventDefault();
    try {
      setSaving(true);
      // await shiftSwapRequestsApi.create(newRequest);

      // Mock save
      setTimeout(() => {
        const targetEmployee = employees.find(e => e.id === parseInt(newRequest.target_employee_id));
        const myShift = shifts.find(s => s.id === parseInt(newRequest.my_shift_id));
        const targetShift = shifts.find(s => s.id === parseInt(newRequest.target_shift_id));

        const newReq = {
          id: requests.length + 1,
          requester_id: currentUser.id,
          requester_name: currentUser.name,
          requester_number: 'EMP001',
          target_id: parseInt(newRequest.target_employee_id),
          target_name: targetEmployee?.name || '',
          target_number: targetEmployee?.employee_number || '',
          my_shift_date: newRequest.my_shift_date,
          my_shift_name: myShift?.name || '',
          my_shift_time: myShift?.time || '',
          target_shift_date: newRequest.target_shift_date,
          target_shift_name: targetShift?.name || '',
          target_shift_time: targetShift?.time || '',
          reason: newRequest.reason,
          status: 'pending_target',
          created_at: new Date().toISOString(),
          target_response: null,
          target_response_date: null,
          manager_response: null,
          manager_response_date: null,
          manager_notes: null
        };

        setRequests([newReq, ...requests]);
        setShowNewRequestModal(false);
        setNewRequest({
          target_employee_id: '',
          my_shift_date: '',
          my_shift_id: '',
          target_shift_date: '',
          target_shift_id: '',
          reason: ''
        });
        setSaving(false);
      }, 500);
    } catch (err) {
      setError('فشل في إنشاء طلب التبديل');
      setSaving(false);
    }
  };

  // Handle action (accept, reject, approve, etc.)
  const handleAction = async () => {
    if (!selectedRequest || !actionType) return;

    try {
      setSaving(true);

      // Mock API calls based on action type
      setTimeout(() => {
        let updatedRequest = { ...selectedRequest };

        switch (actionType) {
          case 'accept':
            updatedRequest.status = 'pending_manager';
            updatedRequest.target_response = 'accepted';
            updatedRequest.target_response_date = new Date().toISOString();
            break;
          case 'reject':
            updatedRequest.status = 'rejected_by_target';
            updatedRequest.target_response = 'rejected';
            updatedRequest.target_response_date = new Date().toISOString();
            updatedRequest.target_rejection_reason = actionReason;
            break;
          case 'approve':
            updatedRequest.status = 'approved';
            updatedRequest.manager_response = 'approved';
            updatedRequest.manager_response_date = new Date().toISOString();
            updatedRequest.manager_notes = actionReason;
            break;
          case 'manager_reject':
            updatedRequest.status = 'rejected_by_manager';
            updatedRequest.manager_response = 'rejected';
            updatedRequest.manager_response_date = new Date().toISOString();
            updatedRequest.manager_notes = actionReason;
            break;
          case 'cancel':
            updatedRequest.status = 'cancelled';
            break;
        }

        setRequests(requests.map(r =>
          r.id === selectedRequest.id ? updatedRequest : r
        ));

        setShowActionModal(false);
        setSelectedRequest(null);
        setActionType(null);
        setActionReason('');
        setSaving(false);
      }, 500);
    } catch (err) {
      setError('فشل في تنفيذ الإجراء');
      setSaving(false);
    }
  };

  // Get status badge
  const getStatusBadge = (status) => {
    const statusConfig = {
      pending_target: { label: 'بانتظار موافقة الزميل', color: 'bg-yellow-100 text-yellow-800' },
      pending_manager: { label: 'بانتظار موافقة المدير', color: 'bg-blue-100 text-blue-800' },
      approved: { label: 'معتمد', color: 'bg-green-100 text-green-800' },
      rejected_by_target: { label: 'مرفوض من الزميل', color: 'bg-red-100 text-red-800' },
      rejected_by_manager: { label: 'مرفوض من المدير', color: 'bg-red-100 text-red-800' },
      cancelled: { label: 'ملغى', color: 'bg-gray-100 text-gray-800' }
    };

    const config = statusConfig[status] || { label: status, color: 'bg-gray-100 text-gray-800' };

    return (
      <span className={`px-2 py-1 text-xs font-medium rounded-full ${config.color}`}>
        {config.label}
      </span>
    );
  };

  // Format date
  const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('ar-SA', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  // Format datetime
  const formatDateTime = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleString('ar-SA', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  // Open action modal
  const openActionModal = (request, type) => {
    setSelectedRequest(request);
    setActionType(type);
    setActionReason('');
    setShowActionModal(true);
  };

  // Get action modal content
  const getActionModalContent = () => {
    const actions = {
      accept: {
        title: 'قبول طلب التبديل',
        message: `هل تريد قبول طلب تبديل الوردية مع ${selectedRequest?.requester_name}؟`,
        confirmText: 'قبول',
        showReason: false
      },
      reject: {
        title: 'رفض طلب التبديل',
        message: `هل تريد رفض طلب تبديل الوردية من ${selectedRequest?.requester_name}؟`,
        confirmText: 'رفض',
        showReason: true,
        reasonLabel: 'سبب الرفض'
      },
      approve: {
        title: 'اعتماد طلب التبديل',
        message: 'هل تريد اعتماد طلب التبديل هذا؟',
        confirmText: 'اعتماد',
        showReason: false,
        reasonLabel: 'ملاحظات (اختياري)'
      },
      manager_reject: {
        title: 'رفض طلب التبديل',
        message: 'هل تريد رفض طلب التبديل هذا؟',
        confirmText: 'رفض',
        showReason: true,
        reasonLabel: 'سبب الرفض'
      },
      cancel: {
        title: 'إلغاء طلب التبديل',
        message: 'هل تريد إلغاء طلب التبديل هذا؟',
        confirmText: 'إلغاء الطلب',
        showReason: false
      }
    };

    return actions[actionType] || {};
  };

  if (loading && requests.length === 0) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <LoadingSpinner size="lg" />
      </div>
    );
  }

  return (
    <div className="space-y-6" dir="rtl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">طلبات تبديل الورديات</h1>
          <p className="text-gray-600 mt-1">إدارة طلبات تبديل الورديات بين الموظفين</p>
        </div>
        <Button onClick={() => setShowNewRequestModal(true)}>
          + طلب تبديل جديد
        </Button>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div className="bg-white rounded-lg shadow p-4 text-center">
          <div className="text-3xl font-bold text-gray-900">{stats.total}</div>
          <div className="text-sm text-gray-600">إجمالي الطلبات</div>
        </div>
        <div className="bg-white rounded-lg shadow p-4 text-center">
          <div className="text-3xl font-bold text-yellow-600">{stats.pending_target}</div>
          <div className="text-sm text-gray-600">بانتظار الزميل</div>
        </div>
        <div className="bg-white rounded-lg shadow p-4 text-center">
          <div className="text-3xl font-bold text-blue-600">{stats.pending_manager}</div>
          <div className="text-sm text-gray-600">بانتظار المدير</div>
        </div>
        <div className="bg-white rounded-lg shadow p-4 text-center">
          <div className="text-3xl font-bold text-green-600">{stats.approved}</div>
          <div className="text-sm text-gray-600">معتمد</div>
        </div>
        {stats.my_pending > 0 && (
          <div className="bg-white rounded-lg shadow p-4 text-center border-2 border-orange-400">
            <div className="text-3xl font-bold text-orange-600">{stats.my_pending}</div>
            <div className="text-sm text-gray-600">طلبات تنتظرني</div>
          </div>
        )}
      </div>

      {/* Tabs */}
      <div className="bg-white rounded-lg shadow">
        <div className="border-b border-gray-200">
          <nav className="flex gap-4 px-4" aria-label="Tabs">
            {[
              { id: 'all', label: 'جميع الطلبات', count: requests.length },
              { id: 'my_requests', label: 'طلباتي', count: requests.filter(r => r.requester_id === currentUser.id).length },
              { id: 'pending_for_me', label: 'تنتظر موافقتي', count: stats.my_pending },
              { id: 'pending_approval', label: 'تنتظر اعتماد المدير', count: stats.pending_manager }
            ].map(tab => (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                className={`py-4 px-2 border-b-2 text-sm font-medium transition-colors ${
                  activeTab === tab.id
                    ? 'border-blue-500 text-blue-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                {tab.label}
                {tab.count > 0 && (
                  <span className={`mr-2 px-2 py-0.5 text-xs rounded-full ${
                    activeTab === tab.id ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-600'
                  }`}>
                    {tab.count}
                  </span>
                )}
              </button>
            ))}
          </nav>
        </div>

        {/* Filters */}
        <div className="p-4 border-b border-gray-200">
          <div className="flex flex-col sm:flex-row gap-4">
            <input
              type="text"
              placeholder="بحث بالاسم أو الرقم الوظيفي..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
            <select
              value={filterStatus}
              onChange={(e) => setFilterStatus(e.target.value)}
              className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="all">جميع الحالات</option>
              <option value="pending_target">بانتظار الزميل</option>
              <option value="pending_manager">بانتظار المدير</option>
              <option value="approved">معتمد</option>
              <option value="rejected_by_target">مرفوض من الزميل</option>
              <option value="rejected_by_manager">مرفوض من المدير</option>
              <option value="cancelled">ملغى</option>
            </select>
          </div>
        </div>

        {/* Requests List */}
        {filteredRequests.length === 0 ? (
          <EmptyState
            title="لا توجد طلبات"
            description="لا توجد طلبات تبديل مطابقة للفلاتر المحددة"
            icon="🔄"
          />
        ) : (
          <div className="divide-y divide-gray-200">
            {filteredRequests.map((request) => (
              <div key={request.id} className="p-4 hover:bg-gray-50">
                <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                  {/* Request Info */}
                  <div className="flex-1">
                    <div className="flex items-center gap-4 mb-2">
                      <span className="font-medium text-gray-900">
                        طلب #{request.id}
                      </span>
                      {getStatusBadge(request.status)}
                      <span className="text-sm text-gray-500">
                        {formatDateTime(request.created_at)}
                      </span>
                    </div>

                    {/* Swap Details */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                      <div className="bg-blue-50 p-3 rounded-lg">
                        <div className="font-medium text-blue-800 mb-1">مقدم الطلب</div>
                        <div className="text-gray-700">{request.requester_name} ({request.requester_number})</div>
                        <div className="text-gray-600 mt-1">
                          {formatDate(request.my_shift_date)} - {request.my_shift_name}
                        </div>
                        <div className="text-xs text-gray-500">{request.my_shift_time}</div>
                      </div>
                      <div className="bg-green-50 p-3 rounded-lg">
                        <div className="font-medium text-green-800 mb-1">مع الزميل</div>
                        <div className="text-gray-700">{request.target_name} ({request.target_number})</div>
                        <div className="text-gray-600 mt-1">
                          {formatDate(request.target_shift_date)} - {request.target_shift_name}
                        </div>
                        <div className="text-xs text-gray-500">{request.target_shift_time}</div>
                      </div>
                    </div>

                    {/* Reason */}
                    <div className="mt-2 text-sm text-gray-600">
                      <span className="font-medium">السبب:</span> {request.reason}
                    </div>

                    {/* Rejection reason if exists */}
                    {request.target_rejection_reason && (
                      <div className="mt-2 text-sm text-red-600">
                        <span className="font-medium">سبب الرفض:</span> {request.target_rejection_reason}
                      </div>
                    )}
                    {request.manager_notes && request.status === 'rejected_by_manager' && (
                      <div className="mt-2 text-sm text-red-600">
                        <span className="font-medium">ملاحظات المدير:</span> {request.manager_notes}
                      </div>
                    )}
                  </div>

                  {/* Actions */}
                  <div className="flex flex-wrap gap-2">
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => {
                        setSelectedRequest(request);
                        setShowDetailsModal(true);
                      }}
                    >
                      التفاصيل
                    </Button>

                    {/* Target employee actions */}
                    {request.target_id === currentUser.id && request.status === 'pending_target' && (
                      <>
                        <Button
                          variant="primary"
                          size="sm"
                          onClick={() => openActionModal(request, 'accept')}
                        >
                          قبول
                        </Button>
                        <Button
                          variant="danger"
                          size="sm"
                          onClick={() => openActionModal(request, 'reject')}
                        >
                          رفض
                        </Button>
                      </>
                    )}

                    {/* Manager actions */}
                    {currentUser.isManager && request.status === 'pending_manager' && (
                      <>
                        <Button
                          variant="primary"
                          size="sm"
                          onClick={() => openActionModal(request, 'approve')}
                        >
                          اعتماد
                        </Button>
                        <Button
                          variant="danger"
                          size="sm"
                          onClick={() => openActionModal(request, 'manager_reject')}
                        >
                          رفض
                        </Button>
                      </>
                    )}

                    {/* Requester can cancel */}
                    {request.requester_id === currentUser.id &&
                      (request.status === 'pending_target' || request.status === 'pending_manager') && (
                      <Button
                        variant="secondary"
                        size="sm"
                        onClick={() => openActionModal(request, 'cancel')}
                      >
                        إلغاء
                      </Button>
                    )}
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Error Message */}
      {error && (
        <div className="bg-red-50 text-red-600 p-4 rounded-lg">
          {error}
        </div>
      )}

      {/* New Request Modal */}
      <Modal
        isOpen={showNewRequestModal}
        onClose={() => setShowNewRequestModal(false)}
        title="طلب تبديل وردية جديد"
        size="lg"
      >
        <form onSubmit={handleSubmitRequest} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              الزميل المطلوب التبديل معه <span className="text-red-500">*</span>
            </label>
            <select
              value={newRequest.target_employee_id}
              onChange={(e) => setNewRequest({ ...newRequest, target_employee_id: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              required
            >
              <option value="">اختر الزميل</option>
              {employees.filter(e => e.id !== currentUser.id).map(emp => (
                <option key={emp.id} value={emp.id}>
                  {emp.name} ({emp.employee_number}) - {emp.department}
                </option>
              ))}
            </select>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {/* My Shift */}
            <div className="space-y-3 p-4 bg-blue-50 rounded-lg">
              <h4 className="font-medium text-blue-800">ورديتي (أريد التبديل منها)</h4>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  التاريخ <span className="text-red-500">*</span>
                </label>
                <input
                  type="date"
                  value={newRequest.my_shift_date}
                  onChange={(e) => setNewRequest({ ...newRequest, my_shift_date: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  required
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  الوردية <span className="text-red-500">*</span>
                </label>
                <select
                  value={newRequest.my_shift_id}
                  onChange={(e) => setNewRequest({ ...newRequest, my_shift_id: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  required
                >
                  <option value="">اختر الوردية</option>
                  {shifts.map(shift => (
                    <option key={shift.id} value={shift.id}>
                      {shift.name} ({shift.time})
                    </option>
                  ))}
                </select>
              </div>
            </div>

            {/* Target Shift */}
            <div className="space-y-3 p-4 bg-green-50 rounded-lg">
              <h4 className="font-medium text-green-800">وردية الزميل (أريد التبديل إليها)</h4>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  التاريخ <span className="text-red-500">*</span>
                </label>
                <input
                  type="date"
                  value={newRequest.target_shift_date}
                  onChange={(e) => setNewRequest({ ...newRequest, target_shift_date: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  required
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  الوردية <span className="text-red-500">*</span>
                </label>
                <select
                  value={newRequest.target_shift_id}
                  onChange={(e) => setNewRequest({ ...newRequest, target_shift_id: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  required
                >
                  <option value="">اختر الوردية</option>
                  {shifts.map(shift => (
                    <option key={shift.id} value={shift.id}>
                      {shift.name} ({shift.time})
                    </option>
                  ))}
                </select>
              </div>
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              سبب طلب التبديل <span className="text-red-500">*</span>
            </label>
            <textarea
              value={newRequest.reason}
              onChange={(e) => setNewRequest({ ...newRequest, reason: e.target.value })}
              rows={3}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="اذكر سبب طلب التبديل..."
              required
            />
          </div>

          <div className="bg-yellow-50 p-3 rounded-lg text-sm text-yellow-800">
            ⚠️ سيتم إرسال الطلب إلى الزميل للموافقة، ثم إلى المدير للاعتماد النهائي
          </div>

          <div className="flex justify-end gap-3 pt-4">
            <Button
              type="button"
              variant="secondary"
              onClick={() => setShowNewRequestModal(false)}
            >
              إلغاء
            </Button>
            <Button type="submit" disabled={saving}>
              {saving ? 'جاري الإرسال...' : 'إرسال الطلب'}
            </Button>
          </div>
        </form>
      </Modal>

      {/* Details Modal */}
      <Modal
        isOpen={showDetailsModal}
        onClose={() => setShowDetailsModal(false)}
        title={`تفاصيل طلب التبديل #${selectedRequest?.id}`}
        size="lg"
      >
        {selectedRequest && (
          <div className="space-y-4">
            {/* Status */}
            <div className="flex items-center gap-2">
              <span className="font-medium">الحالة:</span>
              {getStatusBadge(selectedRequest.status)}
            </div>

            {/* Swap Details */}
            <div className="grid grid-cols-2 gap-4">
              <div className="bg-blue-50 p-4 rounded-lg">
                <h4 className="font-medium text-blue-800 mb-2">مقدم الطلب</h4>
                <div className="space-y-1 text-sm">
                  <div><span className="text-gray-500">الاسم:</span> {selectedRequest.requester_name}</div>
                  <div><span className="text-gray-500">الرقم:</span> {selectedRequest.requester_number}</div>
                  <div><span className="text-gray-500">التاريخ:</span> {formatDate(selectedRequest.my_shift_date)}</div>
                  <div><span className="text-gray-500">الوردية:</span> {selectedRequest.my_shift_name}</div>
                  <div><span className="text-gray-500">الوقت:</span> {selectedRequest.my_shift_time}</div>
                </div>
              </div>
              <div className="bg-green-50 p-4 rounded-lg">
                <h4 className="font-medium text-green-800 mb-2">الزميل</h4>
                <div className="space-y-1 text-sm">
                  <div><span className="text-gray-500">الاسم:</span> {selectedRequest.target_name}</div>
                  <div><span className="text-gray-500">الرقم:</span> {selectedRequest.target_number}</div>
                  <div><span className="text-gray-500">التاريخ:</span> {formatDate(selectedRequest.target_shift_date)}</div>
                  <div><span className="text-gray-500">الوردية:</span> {selectedRequest.target_shift_name}</div>
                  <div><span className="text-gray-500">الوقت:</span> {selectedRequest.target_shift_time}</div>
                </div>
              </div>
            </div>

            {/* Reason */}
            <div>
              <span className="font-medium">السبب:</span>
              <p className="text-gray-600 mt-1">{selectedRequest.reason}</p>
            </div>

            {/* Timeline */}
            <div className="border-t pt-4">
              <h4 className="font-medium mb-3">سجل الإجراءات</h4>
              <div className="space-y-3">
                <div className="flex items-start gap-3">
                  <div className="w-2 h-2 mt-2 bg-blue-500 rounded-full"></div>
                  <div>
                    <div className="text-sm font-medium">تم إنشاء الطلب</div>
                    <div className="text-xs text-gray-500">{formatDateTime(selectedRequest.created_at)}</div>
                  </div>
                </div>

                {selectedRequest.target_response_date && (
                  <div className="flex items-start gap-3">
                    <div className={`w-2 h-2 mt-2 rounded-full ${
                      selectedRequest.target_response === 'accepted' ? 'bg-green-500' : 'bg-red-500'
                    }`}></div>
                    <div>
                      <div className="text-sm font-medium">
                        {selectedRequest.target_response === 'accepted' ? 'قبول الزميل' : 'رفض الزميل'}
                      </div>
                      <div className="text-xs text-gray-500">{formatDateTime(selectedRequest.target_response_date)}</div>
                      {selectedRequest.target_rejection_reason && (
                        <div className="text-xs text-red-600 mt-1">{selectedRequest.target_rejection_reason}</div>
                      )}
                    </div>
                  </div>
                )}

                {selectedRequest.manager_response_date && (
                  <div className="flex items-start gap-3">
                    <div className={`w-2 h-2 mt-2 rounded-full ${
                      selectedRequest.manager_response === 'approved' ? 'bg-green-500' : 'bg-red-500'
                    }`}></div>
                    <div>
                      <div className="text-sm font-medium">
                        {selectedRequest.manager_response === 'approved' ? 'اعتماد المدير' : 'رفض المدير'}
                      </div>
                      <div className="text-xs text-gray-500">{formatDateTime(selectedRequest.manager_response_date)}</div>
                      {selectedRequest.manager_notes && (
                        <div className="text-xs text-gray-600 mt-1">{selectedRequest.manager_notes}</div>
                      )}
                    </div>
                  </div>
                )}
              </div>
            </div>

            <div className="flex justify-end pt-4">
              <Button variant="secondary" onClick={() => setShowDetailsModal(false)}>
                إغلاق
              </Button>
            </div>
          </div>
        )}
      </Modal>

      {/* Action Modal */}
      <Modal
        isOpen={showActionModal}
        onClose={() => setShowActionModal(false)}
        title={getActionModalContent().title}
        size="sm"
      >
        <div className="space-y-4">
          <p className="text-gray-600">{getActionModalContent().message}</p>

          {getActionModalContent().showReason && (
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {getActionModalContent().reasonLabel} <span className="text-red-500">*</span>
              </label>
              <textarea
                value={actionReason}
                onChange={(e) => setActionReason(e.target.value)}
                rows={3}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                required={getActionModalContent().showReason}
              />
            </div>
          )}

          <div className="flex justify-end gap-3 pt-4">
            <Button
              variant="secondary"
              onClick={() => setShowActionModal(false)}
            >
              إلغاء
            </Button>
            <Button
              variant={actionType === 'reject' || actionType === 'manager_reject' || actionType === 'cancel' ? 'danger' : 'primary'}
              onClick={handleAction}
              disabled={saving || (getActionModalContent().showReason && !actionReason)}
            >
              {saving ? 'جاري التنفيذ...' : getActionModalContent().confirmText}
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  );
}
