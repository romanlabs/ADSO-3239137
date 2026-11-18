import { useState } from 'react';
import { mostRequested } from '../api/client.js';
import ErrorMessage from '../components/ErrorMessage.jsx';
import useLoad from '../components/useLoad.js';
import { firstDayOfMonth, formatDate, isoDay } from '../format.js';

/**
 * HU-10, admin only. One row per laptop and frozen category name, most requested first. No students and no assistants
 * in the rows (CA-10.3). An inverted range is answered by the API with 422 INVALID_DATE_RANGE (CA-10.2).
 */
export default function ReportPage() {
  const [draft, setDraft] = useState({ from: firstDayOfMonth(), to: isoDay(new Date()) });
  const [range, setRange] = useState(draft);
  const report = useLoad(() => mostRequested(range.from, range.to), [range.from, range.to]);

  function apply(event) {
    event.preventDefault();
    setRange(draft);
  }

  const rows = report.data?.rows ?? [];
  const top = rows[0]?.times_requested ?? 0;

  return (
    <section>
      <h1>Portátiles más solicitados</h1>
      <form className="toolbar" onSubmit={apply}>
        <label>
          Desde
          <input type="date" value={draft.from} onChange={(e) => setDraft({ ...draft, from: e.target.value })} required />
        </label>
        <label>
          Hasta
          <input type="date" value={draft.to} onChange={(e) => setDraft({ ...draft, to: e.target.value })} required />
        </label>
        <button type="submit">Ver reporte</button>
      </form>

      <ErrorMessage error={report.error} />
      {report.loading && !report.data && <p className="muted">Cargando…</p>}
      {report.data && (
        <>
          <p className="muted">
            Del {formatDate(`${report.data.from}T12:00:00`)} al {formatDate(`${report.data.to}T12:00:00`)}, ambos días
            incluidos.
          </p>
          {rows.length === 0 ? (
            <p className="empty">No hubo préstamos en ese rango.</p>
          ) : (
            <div className="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Portátil</th>
                    <th>Categoría</th>
                    <th>Veces solicitado</th>
                  </tr>
                </thead>
                <tbody>
                  {rows.map((row, index) => (
                    <tr key={`${row.laptop_id}-${row.laptop_name}-${row.category_name}`}>
                      <td>{index + 1}</td>
                      <td>{row.laptop_name}</td>
                      <td>{row.category_name}</td>
                      <td>
                        <span className="bar" style={{ '--share': `${(row.times_requested / top) * 100}%` }}>
                          {row.times_requested}
                        </span>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </>
      )}
    </section>
  );
}
