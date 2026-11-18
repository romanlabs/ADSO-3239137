import { useState } from 'react';
import { Link } from 'react-router-dom';
import { listLoans } from '../api/client.js';
import ErrorMessage from '../components/ErrorMessage.jsx';
import Pagination from '../components/Pagination.jsx';
import StatusBadge from '../components/StatusBadge.jsx';
import useLoad from '../components/useLoad.js';
import { formatDate, formatDateTime } from '../format.js';
import useStudentNames from './useStudentNames.js';

const SIZE = 20;

/** HU-08: list by date range and status, newest first, paginated. The status is computed by the API. */
export default function LoansPage() {
  const [draft, setDraft] = useState({ from: '', to: '', status: '' });
  const [filters, setFilters] = useState({ from: '', to: '', status: '', page: 1 });
  const loans = useLoad(
    () => listLoans({ ...filters, size: SIZE }),
    [filters.from, filters.to, filters.status, filters.page],
  );
  const names = useStudentNames(loans.data?.items.map((loan) => loan.student_id) ?? []);

  function apply(event) {
    event.preventDefault();
    setFilters({ ...draft, page: 1 });
  }

  return (
    <section>
      <div className="page-head">
        <h1>Préstamos</h1>
        <Link className="button" to="/lend">
          Prestar
        </Link>
      </div>

      <form className="toolbar" onSubmit={apply}>
        <label>
          Desde
          <input type="date" value={draft.from} onChange={(e) => setDraft({ ...draft, from: e.target.value })} />
        </label>
        <label>
          Hasta
          <input type="date" value={draft.to} onChange={(e) => setDraft({ ...draft, to: e.target.value })} />
        </label>
        <label>
          Estado
          <select value={draft.status} onChange={(e) => setDraft({ ...draft, status: e.target.value })}>
            <option value="">Todos</option>
            <option value="open">Abiertos</option>
            <option value="overdue">Vencidos</option>
            <option value="closed">Cerrados</option>
          </select>
        </label>
        <button type="submit">Filtrar</button>
      </form>

      <ErrorMessage error={loans.error} />
      {loans.loading && !loans.data && <p className="muted">Cargando…</p>}
      {loans.data && (
        <>
          {loans.data.items.length === 0 ? (
            <p className="empty">No hay préstamos con esos filtros.</p>
          ) : (
            <div className="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Fecha</th>
                    <th>Estudiante</th>
                    <th>Portátil</th>
                    <th>Vence</th>
                    <th>Estado</th>
                  </tr>
                </thead>
                <tbody>
                  {loans.data.items.map((loan) => (
                    <tr key={loan.id}>
                      <td>
                        <Link to={`/loans/${loan.id}`}>{formatDateTime(loan.loaned_at)}</Link>
                      </td>
                      <td>
                        <Link to={`/students/${loan.student_id}`}>{names[loan.student_id] ?? '…'}</Link>
                      </td>
                      <td>{loan.items.map((item) => item.laptop_name).join(', ')}</td>
                      <td>{formatDate(loan.items[0]?.due_at)}</td>
                      <td>
                        <StatusBadge status={loan.status} />
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
          <Pagination
            page={loans.data.page}
            size={loans.data.size}
            total={loans.data.total}
            onChange={(page) => setFilters((current) => ({ ...current, page }))}
          />
        </>
      )}
    </section>
  );
}
