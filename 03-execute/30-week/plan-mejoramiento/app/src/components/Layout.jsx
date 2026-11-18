import { NavLink, Outlet, useNavigate } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext.jsx';

const LINKS = [
  { to: '/laptops', label: 'Inventario', roles: ['admin', 'assistant'] },
  { to: '/students', label: 'Estudiantes', roles: ['admin', 'assistant'] },
  { to: '/lend', label: 'Prestar', roles: ['admin', 'assistant'] },
  { to: '/loans', label: 'Préstamos', roles: ['admin', 'assistant'] },
  { to: '/overdue', label: 'Vencidos', roles: ['admin', 'assistant'] },
  { to: '/report', label: 'Más solicitados', roles: ['admin'] },
  { to: '/assistants', label: 'Auxiliares', roles: ['admin'] },
];

const ROLE_LABEL = { admin: 'Administrador', assistant: 'Auxiliar' };

export default function Layout() {
  const { session, signOut } = useAuth();
  const navigate = useNavigate();
  const links = LINKS.filter((link) => link.roles.includes(session.role));

  return (
    <div className="shell">
      <header className="topbar">
        <span className="brand">
          <span className="brand-mark" aria-hidden="true" />
          Préstamo de portátiles
        </span>
        <nav className="menu" aria-label="Secciones">
          {links.map((link) => (
            <NavLink key={link.to} to={link.to}>
              {link.label}
            </NavLink>
          ))}
        </nav>
        <div className="who">
          <span>
            {session.username} · {ROLE_LABEL[session.role] ?? session.role}
          </span>
          {/* An explicit exit goes to a clean /login: the next user must not land on the last page of this one. */}
          <button
            type="button"
            className="ghost"
            onClick={() => {
              signOut();
              navigate('/login', { replace: true });
            }}
          >
            Salir
          </button>
        </div>
      </header>
      <main className="content">
        <Outlet />
      </main>
    </div>
  );
}
