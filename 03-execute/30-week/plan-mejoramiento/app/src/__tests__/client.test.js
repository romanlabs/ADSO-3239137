import { describe, expect, it, vi } from 'vitest';
import { API_BASE, ApiError, configureClient, request } from '../api/client.js';

function response(status, body) {
  return {
    ok: status >= 200 && status < 300,
    status,
    json: async () => body,
    blob: async () => new Blob(['x']),
  };
}

describe('api client', () => {
  it('calls /api/v1 with the bearer token and query parameters', async () => {
    configureClient({ getToken: () => 'tok', onUnauthorized: () => {} });
    const fetchImpl = vi.fn(async () => response(200, { items: [] }));

    await request('GET', '/laptops', { params: { q: 'think', category_id: '', page: 2 }, fetchImpl });

    const [url, init] = fetchImpl.mock.calls[0];
    expect(API_BASE.endsWith('/api/v1')).toBe(true);
    expect(url).toBe(`${API_BASE}/laptops?q=think&page=2`);
    expect(init.headers.Authorization).toBe('Bearer tok');
  });

  it('turns the single error body into an ApiError with code, message and details', async () => {
    const fetchImpl = async () =>
      response(409, { code: 'LAPTOP_NOT_AVAILABLE', message: 'No quedan unidades.', details: [{ field: 'items[0].laptop_id' }] });

    const error = await request('POST', '/loans', { body: {}, fetchImpl }).catch((e) => e);

    expect(error).toBeInstanceOf(ApiError);
    expect(error.status).toBe(409);
    expect(error.code).toBe('LAPTOP_NOT_AVAILABLE');
    expect(error.message).toBe('No quedan unidades.');
    expect(error.details).toHaveLength(1);
  });

  it('closes the session on a 401 from an authenticated call, not from the login', async () => {
    const onUnauthorized = vi.fn();
    configureClient({ getToken: () => 'old', onUnauthorized });
    const fetchImpl = async () => response(401, { code: 'INVALID_CREDENTIALS', message: 'Credenciales inválidas.', details: [] });

    await request('GET', '/categories', { fetchImpl }).catch(() => {});
    await request('POST', '/auth/login', { body: {}, auth: false, fetchImpl }).catch(() => {});

    expect(onUnauthorized).toHaveBeenCalledTimes(1);
  });

  it('reports a network failure with its own code', async () => {
    const error = await request('GET', '/health', { fetchImpl: async () => { throw new TypeError('down'); } }).catch((e) => e);
    expect(error.code).toBe('NETWORK_ERROR');
    expect(error.status).toBe(0);
  });

  it('returns null for 204 responses', async () => {
    expect(await request('DELETE', '/laptops/1', { as: 'none', fetchImpl: async () => response(204) })).toBeNull();
  });
});
