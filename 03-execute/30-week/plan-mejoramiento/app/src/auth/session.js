// Token and role live in memory and in sessionStorage (docs/architecture.md): closing the tab ends the session.
// The token is not decoded here; the role comes from the login response. The API checks the role on every call anyway.

const KEY = 'laptops.session';

function storage() {
  try {
    return globalThis.sessionStorage ?? null;
  } catch {
    return null;
  }
}

/** Reads a stored session, or null if missing, malformed or past `expires_at`. */
export function loadSession(now = Date.now()) {
  try {
    const raw = storage()?.getItem(KEY);
    if (!raw) return null;
    const session = JSON.parse(raw);
    if (!session?.token || !session?.role || !(session.expiresAt > now)) return null;
    return session;
  } catch {
    return null;
  }
}

/** Builds a session from the body of POST /auth/login. */
export function sessionFromLogin(username, body, now = Date.now()) {
  return {
    token: body.access_token,
    role: body.role,
    username: username.trim().toLowerCase(),
    expiresAt: now + body.expires_in * 1000,
  };
}

export function saveSession(session) {
  try {
    storage()?.setItem(KEY, JSON.stringify(session));
  } catch {
    // Without storage the session still works in memory until the tab is reloaded.
  }
}

export function clearSession() {
  try {
    storage()?.removeItem(KEY);
  } catch {
    // Nothing stored, nothing to clear.
  }
}
