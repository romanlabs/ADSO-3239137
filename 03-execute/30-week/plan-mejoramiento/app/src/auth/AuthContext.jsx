import { createContext, useCallback, useContext, useMemo, useRef, useState } from 'react';
import { configureClient, login as loginRequest } from '../api/client.js';
import { clearSession, loadSession, saveSession, sessionFromLogin } from './session.js';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [session, setSession] = useState(() => loadSession());
  const [notice, setNotice] = useState('');
  const sessionRef = useRef(session);
  sessionRef.current = session;

  const signOut = useCallback((message = '') => {
    clearSession();
    setSession(null);
    setNotice(message);
  }, []);

  // The client reads the token from here and, on any 401, closes the session (expired or invalid token, CA-01.3).
  // Configured during render, not in an effect: child effects run first and their requests already need the token.
  configureClient({
    getToken: () => sessionRef.current?.token ?? null,
    onUnauthorized: () => signOut('Tu sesión terminó. Vuelve a iniciar sesión.'),
  });

  const signIn = useCallback(async (username, password) => {
    const body = await loginRequest(username, password);
    const next = sessionFromLogin(username, body);
    saveSession(next);
    setNotice('');
    setSession(next);
    return next;
  }, []);

  const value = useMemo(
    () => ({
      session,
      role: session?.role ?? null,
      isAdmin: session?.role === 'admin',
      notice,
      signIn,
      signOut,
    }),
    [session, notice, signIn, signOut],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const value = useContext(AuthContext);
  if (!value) throw new Error('useAuth must be used inside <AuthProvider>');
  return value;
}
