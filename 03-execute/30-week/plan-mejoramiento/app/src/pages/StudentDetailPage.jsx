import { Link, useParams } from 'react-router-dom';
import { getStudent, listLoans } from '../api/client.js';
import ErrorMessage from '../components/ErrorMessage.jsx';
import StatusBadge from '../components/StatusBadge.jsx';
import useLoad from '../components/useLoad.js';
import { formatDate, formatDateTime } from '../format.js';

/** A student with the count of laptops not returned (active_loans) and their loans, newest first (HU-05, HU-08). */
export default function StudentDetailPage() {
  const { id } = useParams();
  const student = useLoad(() => getStudent(id), [id]);
  const loans = useLoad(() => listLoans({ studentId: id, size: 50 }), [id]);

  if (student.loading && !student.data) return <p className="muted">Cargando…</p>;
  if (student.error) {
    return (
      <section>
        <ErrorMessage error={student.error} />
        <Link to="/students">Volver a estudiantes</Link>
      </section>
    );
  }

  const data = student.data;
  const hasOverdue = loans.data?.items.some((loan) => loan.status === 'overdue');

  return (
    <section>
      <p>
        <Link to="/students">← Estudiantes</Link>
      </p>
      <div className="page-head">
        <div>
          <h1>{data.full_name}</h1>
          <p className="muted">Documento {data.document_number}</p>
        </div>
        {data.active_loans === 0 && !hasOverdue && (
          <Link className="button" to={`/lend?student=${data.id}`}>
            Prestar un portátil
          </Link>
        )}
      </div>
      <p className="panel">
        {data.active_loans === 0
          ? 'No tiene portátiles sin devolver.'
          : `Tiene ${data.active_loans} portátil sin devolver. Debe devolverlo antes de pedir otro.`}
      </p>

      <h2>Préstamos</h2>
      <ErrorMessage error={loans.error} />
      {loans.data && loans.data.items.length === 0 && <p className="empty">Todavía no tiene préstamos.</p>}
      {loans.data && loans.data.items.length > 0 && (
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Fecha</th>
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
    </section>
  );
}
