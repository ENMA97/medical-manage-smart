import { describe, it, expect, vi, beforeEach } from 'vitest';

// Mock localStorage
const localStorageMock = {
  store: {},
  getItem: vi.fn((key) => localStorageMock.store[key] || null),
  setItem: vi.fn((key, value) => { localStorageMock.store[key] = value; }),
  removeItem: vi.fn((key) => { delete localStorageMock.store[key]; }),
  clear: vi.fn(() => { localStorageMock.store = {}; }),
};
Object.defineProperty(global, 'localStorage', { value: localStorageMock });

// Mock import.meta.env
vi.stubEnv('VITE_API_URL', '/api');

describe('API Service', () => {
  beforeEach(() => {
    localStorageMock.clear();
    vi.clearAllMocks();
  });

  it('should create axios instance with correct baseURL', async () => {
    const { default: api } = await import('../services/api');
    expect(api.defaults.baseURL).toBe('/api');
  });

  it('should set Content-Type header to JSON', async () => {
    const { default: api } = await import('../services/api');
    expect(api.defaults.headers['Content-Type']).toBe('application/json');
  });

  it('should set Accept header to JSON', async () => {
    const { default: api } = await import('../services/api');
    expect(api.defaults.headers['Accept']).toBe('application/json');
  });

  it('should have 30 second timeout', async () => {
    const { default: api } = await import('../services/api');
    expect(api.defaults.timeout).toBe(30000);
  });
});
