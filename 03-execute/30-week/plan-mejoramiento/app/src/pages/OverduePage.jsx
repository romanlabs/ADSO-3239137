import { Link } from 'react-router-dom';
import { listOverdue } from '../api/client.js';
import ErrorMessage from '../components/ErrorMessage.jsx';
import useLoad from '../components/useLoad.js';
import { formatDateTime } from '../format.js';

/** HU-09: lines not returned whose due date already passed, most days late first (the API orders them). */
export default function OverduePage() {
  const overdue = useLoad(() => listOverdue(), []);

  return (
    <section>
      <div className="page-head">
        <h1>Vencidos</h1>
        <button type="button" className="ghost" onClick={overdue.reload}>
          Actualizar
        </button>
      </div>
      <p className="muted">Un estudiante con un portátil vencido no puede pedir otro hasta devolverlo.</p>

      <ErrorMessage error={overdue.error} />
      {overdue.loading && !overdue.data && <p className="muted">Cargando…</p>}
      {overdue.data && overdue.data.items.length === 0 && <p className="empty">No hay portátiles vencidos.</p>}
      {overdue.data && overdue.data.items.length > 0 && (
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Días de retraso</th>
                <th>Portátil</th>
                <th>Estudiante</th>
                <th>Vencía</th>
                <th aria-label="Acciones" />
              </tr>
            </thead>
            <tbody>
              {overdue.data.items.map((line) => (
                <tr key={line.item_id}>
                  <td>
                    <span className="badge badge-overdue">{line.days_late}</span>
                  </td>
                  <td>{line.laptop_name}</td>
                  <td>{line.student_name}</td>
                  <td>{formatDateTime(line.due_at)}</td>
                  <td className="right">
                    <Link to={`/loans/${line.loan_id}`}>Registrar devolución</Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </section>
  );
}
