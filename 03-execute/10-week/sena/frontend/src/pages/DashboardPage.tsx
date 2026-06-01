import { useEffect, useState } from 'react';
import { aprendicesApi } from '../api/aprendices.api';
import { matriculasApi } from '../api/matriculas.api';
import { StatCard } from '../components/StatCard';
import { Aprendiz } from '../types/aprendiz';
import { Matricula } from '../types/matricula';
import { statusClassName } from '../utils/format';

export function DashboardPage() {
  const [aprendices, setAprendices] = useState<Aprendiz[]>([]);
  const [matriculas, setMatriculas] = useState<Matricula[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    Promise.all([aprendicesApi.list(), matriculasApi.list()])
      .then(([aprendicesData, matriculasData]) => {
        setAprendices(aprendicesData);
        setMatriculas(matriculasData);
      })
      .finally(() => setLoading(false));
  }, []);

  const totalCondicionados = matriculas.filter((item) => item.estado === 'Condicionado').length;
  const totalCancelados = matriculas.filter((item) => item.estado === 'Cancelado').length;
  const totalActivos = matriculas.filter((item) => item.estado === 'En formacion').length;

  return (
    <section className="page">
      <div className="page-header">
        <div>
          <h2>Bienvenido al Sistema de Matrícula SENA</h2>
          <p>Selecciona una opción del menú para comenzar.</p>
        </div>
      </div>

      {loading ? (
        <p className="muted">Cargando indicadores...</p>
      ) : (
        <>
          <div className="stats-grid">
            <StatCard label="Aprendices" value={aprendices.length} tone="green" />
            <StatCard label="Matrículas activas" value={totalActivos} tone="blue" />
            <StatCard label="Condicionados" value={totalCondicionados} tone="amber" />
            <StatCard label="Cancelados" value={totalCancelados} tone="red" />
          </div>

          <section className="panel">
            <h2>Últimas matrículas</h2>
            <div className="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Aprendiz</th>
                    <th>Ficha</th>
                    <th>Jornada</th>
                    <th>Estado</th>
                  </tr>
                </thead>
                <tbody>
                  {matriculas.slice(0, 5).map((matricula) => {
                    const aprendiz = typeof matricula.aprendizId === 'string' ? null : matricula.aprendizId;
                    return (
                      <tr key={matricula._id}>
                        <td>{aprendiz?.nombre || 'Sin aprendiz'}</td>
                        <td>{matricula.ficha}</td>
                        <td>{matricula.jornada}</td>
                        <td>
                          <span className={`badge ${statusClassName(matricula.estado)}`}>
                            {matricula.estado}
                          </span>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          </section>
        </>
      )}
    </section>
  );
}
