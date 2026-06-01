import { NavLink, Outlet } from 'react-router-dom';

export function Layout() {
  return (
    <div className="app">
      <header>
        <h1>SENA - Sistema de Matricula</h1>
        <nav>
          <NavLink to="/">Inicio</NavLink>
          <NavLink to="/aprendices">Aprendices</NavLink>
          <NavLink to="/matriculas">Matriculas</NavLink>
        </nav>
      </header>
      <main>
        <Outlet />
      </main>
    </div>
  );
}
