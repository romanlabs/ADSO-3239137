import { useState } from 'react';
import { Navigate, useLocation, useNavigate } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext.jsx';
import ErrorMessage from '../components/ErrorMessage.jsx';

/** HU-01. A wrong user or password shows the API message, which never says which of the two failed (CA-01.2). */
export default function LoginPage() {
  const { session, signIn, notice } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState(null);
  const [busy, setBusy] = useState(false);

  if (session) return <Navigate to={location.state?.from ?? '/laptops'} replace />;

  async function submit(event) {
    event.preventDefault();
    setBusy(true);
    setError(null);
    try {
      await signIn(username, password);
      navigate(location.state?.from ?? '/laptops', { replace: true });
    } catch (err) {
      setError(err);
      setPassword('');
    } finally {
      setBusy(false);
    }
  }

  return (
    <main className="login">
      <form className="panel login-card" onSubmit={submit}>
        <span className="brand">
          <span className="brand-mark" aria-hidden="true" />
          Préstamo de portátiles
        </span>
        <h1>Iniciar sesión</h1>
        {notice && <p className="alert alert-info">{notice}</p>}
        <ErrorMessage error={error} />
        <label>
          Usuario
          <input value={username} onChange={(e) => setUsername(e.target.value)} autoComplete="username" required autoFocus />
        </label>
        <label>
          Contraseña
          <input
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            autoComplete="current-password"
            required
          />
        </label>
        <button type="submit" disabled={busy}>
          {busy ? 'Entrando…' : 'Entrar'}
        </button>
      </form>
    </main>
  );
}
