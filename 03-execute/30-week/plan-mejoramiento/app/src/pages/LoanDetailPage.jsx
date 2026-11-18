import { useState } from 'react';
import { Link, useLocation, useParams } from 'react-router-dom';
import { getLoan, getStudent, returnLoanItem } from '../api/client.js';
import ErrorMessage from '../components/ErrorMessage.jsx';
import StatusBadge from '../components/StatusBadge.jsx';
import useLoad from '../components/useLoad.js';
import { formatDateTime } from '../format.js';

/**
 * HU-07 and HU-08: the loan with its lines and computed status. Each line is returned on its own; a second return of
 * the same line gets 409 ITEM_ALREADY_RETURNED from the API and never adds a unit twice (CA-07.2).
 */
export default function LoanDetailPage() {
  const { id } = useParams();
  const location = useLocation();
  const loan = useLoad(() => getLoan(id), [id]);
  const student = useLoad(() => (loan.data ? getStudent(loan.data.student_id) : Promise.resolve(null)), [loan.data?.student_id]);
  const [error, setError] = useState(null);
  const [returning, setReturning] = useState(null);
  const [message, setMessage] = useState(location.state?.justCreated ? 'Préstamo registrado.' : '');

  async function giveBack(item) {
    setReturning(item.id);
    setError(null);
    setMessage('');
    try {
      await returnLoanItem(id, item.id);
      setMessage(`Se registró la devolución de ${item.laptop_name}.`);
      loan.reload();
    } catch (err) {
      setError(err);
      loan.reload();
    } finally {
      setReturning(null);
    }
  }

  if (loan.loading && !loan.data) return <p className="muted">Cargando…</p>;
  if (loan.error && !loan.data) {
    return (
      <section>
        <ErrorMessage error={loan.error} />
        <Link to="/loans">Volver a préstamos</Link>
      </section>
    );
  }

  const data = loan.data;
  return (
    <section>
      <p>
        <Link to="/loans">← Préstamos</Link>
      </p>
      <div className="page-head">
        <div>
          <h1>Préstamo</h1>
          <p className="muted">
            {formatDateTime(data.loaned_at)} ·{' '}
            {student.data ? <Link to={`/students/${data.student_id}`}>{student.data.full_name}</Link> : '…'}
          </p>
        </div>
        <StatusBadge status={data.status} />
      </div>

      {message && <p className="alert alert-ok">{message}</p>}
      <ErrorMessage error={error} />

      <div className="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Portátil</th>
              <th>Código</th>
              <th>Categoría</th>
              <th>Vence</th>
              <th>Devuelto</th>
              <th aria-label="Acciones" />
            </tr>
          </thead>
          <tbody>
            {data.items.map((item) => (
              <tr key={item.id}>
                <td>{item.laptop_name}</td>
                <td>
                  <code>{item.model_code}</code>
                </td>
                <td>{item.category_name}</td>
                <td>{formatDateTime(item.due_at)}</td>
                <td>{item.returned_at ? formatDateTime(item.returned_at) : 'Pendiente'}</td>
                <td className="right">
                  {!item.returned_at && (
                    <button type="button" disabled={returning !== null} onClick={() => giveBack(item)}>
                      {returning === item.id ? 'Registrando…' : 'Registrar devolución'}
                    </button>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <p className="muted small">
        El nombre, el código y la categoría quedan como estaban el día del préstamo, aunque luego cambien en el inventario.
      </p>
    </section>
  );
}
