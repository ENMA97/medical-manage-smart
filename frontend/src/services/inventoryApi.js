import api from './api';

/**
 * خدمات API للمستودعات
 * Warehouses API Services
 */
export const warehousesApi = {
  getAll: (params = {}) => api.get('/inventory/warehouses', { params }),
  getActive: () => api.get('/inventory/warehouses/active'),
  getById: (id) => api.get(`/inventory/warehouses/${id}`),
  create: (data) => api.post('/inventory/warehouses', data),
  update: (id, data) => api.put(`/inventory/warehouses/${id}`, data),
  delete: (id) => api.delete(`/inventory/warehouses/${id}`),

  // Stock
  getStock: (id, params = {}) => api.get(`/inventory/warehouses/${id}/stock`, { params }),
  getLowStock: (id) => api.get(`/inventory/warehouses/${id}/low-stock`),
  getExpiringItems: (id, days = 30) => api.get(`/inventory/warehouses/${id}/expiring`, { params: { days } }),

  // Types
  getTypes: () => api.get('/inventory/warehouses/types'),
};

/**
 * خدمات API لتصنيفات الأصناف
 * Item Categories API Services
 */
export const itemCategoriesApi = {
  getAll: (params = {}) => api.get('/inventory/categories', { params }),
  getActive: () => api.get('/inventory/categories/active'),
  getById: (id) => api.get(`/inventory/categories/${id}`),
  create: (data) => api.post('/inventory/categories', data),
  update: (id, data) => api.put(`/inventory/categories/${id}`, data),
  delete: (id) => api.delete(`/inventory/categories/${id}`),
  getTree: () => api.get('/inventory/categories/tree'),
};

/**
 * خدمات API للأصناف
 * Inventory Items API Services
 */
export const inventoryItemsApi = {
  getAll: (params = {}) => api.get('/inventory/items', { params }),
  getActive: () => api.get('/inventory/items/active'),
  getById: (id) => api.get(`/inventory/items/${id}`),
  create: (data) => api.post('/inventory/items', data),
  update: (id, data) => api.put(`/inventory/items/${id}`, data),
  delete: (id) => api.delete(`/inventory/items/${id}`),

  // Stock info
  getStockLevels: (id) => api.get(`/inventory/items/${id}/stock-levels`),
  getMovementHistory: (id, params = {}) => api.get(`/inventory/items/${id}/movements`, { params }),

  // Low stock & expiring
  getLowStock: () => api.get('/inventory/items/low-stock'),
  getExpiring: (days = 30) => api.get('/inventory/items/expiring', { params: { days } }),

  // Search
  search: (query) => api.get('/inventory/items/search', { params: { q: query } }),

  // Barcode
  getByBarcode: (barcode) => api.get(`/inventory/items/barcode/${barcode}`),

  // Export
  exportList: (params = {}) => api.get('/inventory/items/export', { params, responseType: 'blob' }),
};

/**
 * خدمات API لحركات المخزون
 * Inventory Movements API Services
 */
export const inventoryMovementsApi = {
  getAll: (params = {}) => api.get('/inventory/movements', { params }),
  getById: (id) => api.get(`/inventory/movements/${id}`),
  create: (data) => api.post('/inventory/movements', data),

  // Movement types
  receive: (data) => api.post('/inventory/movements/receive', data),
  issue: (data) => api.post('/inventory/movements/issue', data),
  transfer: (data) => api.post('/inventory/movements/transfer', data),
  adjust: (data) => api.post('/inventory/movements/adjust', data),
  return: (data) => api.post('/inventory/movements/return', data),

  // Batch operations
  batchReceive: (data) => api.post('/inventory/movements/batch-receive', data),
  batchIssue: (data) => api.post('/inventory/movements/batch-issue', data),

  // By warehouse
  getByWarehouse: (warehouseId, params = {}) =>
    api.get(`/inventory/warehouses/${warehouseId}/movements`, { params }),

  // Export
  exportList: (params = {}) => api.get('/inventory/movements/export', { params, responseType: 'blob' }),
};

/**
 * خدمات API لمخزون المستودعات
 * Warehouse Stock API Services
 */
export const warehouseStockApi = {
  getAll: (params = {}) => api.get('/inventory/stock', { params }),
  getByWarehouse: (warehouseId, params = {}) =>
    api.get(`/inventory/warehouses/${warehouseId}/stock`, { params }),
  getByItem: (itemId) => api.get(`/inventory/items/${itemId}/stock`),
  adjust: (data) => api.post('/inventory/stock/adjust', data),
};

/**
 * خدمات API للحصص
 * Item Quotas API Services
 */
export const itemQuotasApi = {
  getAll: (params = {}) => api.get('/inventory/quotas', { params }),
  getById: (id) => api.get(`/inventory/quotas/${id}`),
  create: (data) => api.post('/inventory/quotas', data),
  update: (id, data) => api.put(`/inventory/quotas/${id}`, data),
  delete: (id) => api.delete(`/inventory/quotas/${id}`),

  // By department
  getByDepartment: (departmentId) => api.get(`/inventory/quotas/department/${departmentId}`),

  // Consumption
  getConsumption: (id, params = {}) => api.get(`/inventory/quotas/${id}/consumption`, { params }),
  recordConsumption: (id, data) => api.post(`/inventory/quotas/${id}/consume`, data),

  // Reports
  getUsageReport: (params = {}) => api.get('/inventory/quotas/usage-report', { params }),
};

/**
 * خدمات API لطلبات الشراء
 * Purchase Requests API Services
 */
export const purchaseRequestsApi = {
  getAll: (params = {}) => api.get('/inventory/purchase-requests', { params }),
  getById: (id) => api.get(`/inventory/purchase-requests/${id}`),
  create: (data) => api.post('/inventory/purchase-requests', data),
  update: (id, data) => api.put(`/inventory/purchase-requests/${id}`, data),
  delete: (id) => api.delete(`/inventory/purchase-requests/${id}`),

  // Workflow
  submit: (id) => api.post(`/inventory/purchase-requests/${id}/submit`),
  approveManager: (id, data) => api.post(`/inventory/purchase-requests/${id}/approve/manager`, data),
  approveFinance: (id, data) => api.post(`/inventory/purchase-requests/${id}/approve/finance`, data),
  approveCEO: (id, data) => api.post(`/inventory/purchase-requests/${id}/approve/ceo`, data),
  reject: (id, data) => api.post(`/inventory/purchase-requests/${id}/reject`, data),
  complete: (id, data) => api.post(`/inventory/purchase-requests/${id}/complete`, data),

  // Items
  addItem: (id, data) => api.post(`/inventory/purchase-requests/${id}/items`, data),
  updateItem: (id, itemId, data) => api.put(`/inventory/purchase-requests/${id}/items/${itemId}`, data),
  removeItem: (id, itemId) => api.delete(`/inventory/purchase-requests/${id}/items/${itemId}`),

  // Pending approvals
  getPendingApprovals: (role) => api.get(`/inventory/purchase-requests/pending/${role}`),

  // Export
  exportList: (params = {}) => api.get('/inventory/purchase-requests/export', { params, responseType: 'blob' }),
};

/**
 * خدمات تقارير المخزون
 * Inventory Reports API Services
 */
export const inventoryReportsApi = {
  getStockSummary: () => api.get('/inventory/reports/stock-summary'),
  getMovementSummary: (params = {}) => api.get('/inventory/reports/movement-summary', { params }),
  getExpiringItems: (days = 30) => api.get('/inventory/reports/expiring', { params: { days } }),
  getLowStockItems: () => api.get('/inventory/reports/low-stock'),
  getConsumptionReport: (params = {}) => api.get('/inventory/reports/consumption', { params }),
  getValuationReport: () => api.get('/inventory/reports/valuation'),
  getABCAnalysis: () => api.get('/inventory/reports/abc-analysis'),
};
