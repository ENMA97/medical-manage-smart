import api from './api';
import { isDemoMode } from './demoAuth';
import { demoShifts } from './demoHrms';

const shiftService = {
  getAll: (params = {}) => (isDemoMode() ? demoShifts.getAll(params) : api.get('/shifts', { params })),
  getById: (id) => (isDemoMode() ? demoShifts.getById(id) : api.get(`/shifts/${id}`)),
  create: (data) => (isDemoMode() ? demoShifts.create(data) : api.post('/shifts', data)),
  update: (id, data) => (isDemoMode() ? demoShifts.update(id, data) : api.put(`/shifts/${id}`, data)),
  delete: (id) => (isDemoMode() ? demoShifts.delete(id) : api.delete(`/shifts/${id}`)),
  assign: (id, data) => (isDemoMode() ? demoShifts.assign(id, data) : api.post(`/shifts/${id}/assign`, data)),
  getAssignments: (params = {}) => (isDemoMode() ? demoShifts.getAssignments(params) : api.get('/shift-assignments', { params })),
  unassign: (assignmentId) => (isDemoMode() ? demoShifts.unassign(assignmentId) : api.delete(`/shift-assignments/${assignmentId}`)),
};

export default shiftService;
