import { describe, expect, it } from 'vitest';
import { loadSession, saveSession, sessionFromLogin } from '../auth/session.js';

function memoryStorage() {
  const data = new Map();
  return { getItem: (k) => data.get(k) ?? null, setItem: (k, v) => data.set(k, v), removeItem: (k) => data.delete(k) };
}

describe('session', () => {
  it('builds the session from the login body with an expiry of expires_in seconds', () => {
    const session = sessionFromLogin(' Camila ', { access_token: 't', role: 'assistant', expires_in: 28800 }, 1000);
    expect(session).toEqual({ token: 't', role: 'assistant', username: 'camila', expiresAt: 1000 + 28800 * 1000 });
  });

  it('drops a stored session once it expires', () => {
    globalThis.sessionStorage = memoryStorage();
    saveSession({ token: 't', role: 'admin', username: 'admin', expiresAt: 5000 });

    expect(loadSession(4999)?.role).toBe('admin');
    expect(loadSession(5000)).toBeNull();
    delete globalThis.sessionStorage;
  });
});
