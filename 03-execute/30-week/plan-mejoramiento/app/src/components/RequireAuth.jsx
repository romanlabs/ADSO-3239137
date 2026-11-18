import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext.jsx';

/** Sends to /login without a session. With `roles`, a user with another role sees a 403 notice instead of the page. */
export default function RequireAuth({ roles, children }) {
  const { session } = useAuth();
  const location = useLocation();

  if (!session) return <Navigate to="/login" replace state={{ from: location.pathname }} />;
  if (roles && !roles.includes(session.role)) {
    return (
      <section className="panel">
        <h1>Sin permiso</h1>
        <p>Esta sección es solo para el administrador.</p>
      </section>
    );
  }
  return children;
}
