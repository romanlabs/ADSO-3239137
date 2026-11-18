import { useState } from 'react';
import { createAssistant } from '../api/client.js';
import ErrorMessage from '../components/ErrorMessage.jsx';

/** HU-02. Only the admin; the role sent is always "assistant", the admin role is never granted from here (CA-02.3). */
export default function AssistantsPage() {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState(null);
  const [created, setCreated] = useState([]);
  const [busy, setBusy] = useState(false);

  async function submit(event) {
    event.preventDefault();
    setBusy(true);
    setError(null);
    try {
      const user = await createAssistant(username, password);
      setCreated((list) => [user, ...list]);
      setUsername('');
      setPassword('');
    } catch (err) {
      setError(err);
    } finally {
      setBusy(false);
    }
  }

  return (
    <section>
      <h1>Auxiliares</h1>
      <p className="muted">Los auxiliares registran préstamos y devoluciones. El nombre se guarda en minúsculas.</p>
      <form className="panel form-grid" onSubmit={submit}>
        <ErrorMessage error={error} />
        <label>
          Usuario
          <input value={username} onChange={(e) => setUsername(e.target.value)} required />
        </label>
        <label>
          Contraseña (mínimo 8 caracteres)
          <input type="password" value={password} onChange={(e) => setPassword(e.target.value)} minLength={8} required autoComplete="new-password" />
        </label>
        <div className="actions">
          <button type="submit" disabled={busy}>Crear auxiliar</button>
        </div>
      </form>
      {created.length > 0 && (
        <div className="panel">
          <h2>Creados en esta sesión</h2>
          <ul className="plain">
            {created.map((user) => (
              <li key={user.id}>
                <strong>{user.username}</strong> · auxiliar
              </li>
            ))}
          </ul>
        </div>
      )}
    </section>
  );
}
