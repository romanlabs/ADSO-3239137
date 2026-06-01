import { Routes, Route, Link } from 'react-router-dom'
import { AprendicesPage } from './pages/AprendicesPage'
import { DashboardPage } from './pages/DashboardPage'
import { MatriculasPage } from './pages/MatriculasPage'
import './App.css'

function App() {
  return (
    <div className="app">
      <header>
        <h1>SENA - Sistema de Matrícula</h1>
        <nav>
          <Link to="/">Inicio</Link>
          <Link to="/aprendices">Aprendices</Link>
          <Link to="/matriculas">Matrículas</Link>
        </nav>
      </header>
      <main>
        <Routes>
          <Route path="/" element={<DashboardPage />} />
          <Route path="/aprendices" element={<AprendicesPage />} />
          <Route path="/matriculas" element={<MatriculasPage />} />
        </Routes>
      </main>
    </div>
  )
}

export default App
