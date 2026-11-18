import { useState } from 'react';
import { Link } from 'react-router-dom';
import { createStudent, searchStudents } from '../api/client.js';
import ErrorMessage from '../components/ErrorMessage.jsx';
import Pagination from '../components/Pagination.jsx';
import useLoad from '../components/useLoad.js';

const SIZE = 20;

/** HU-05: admin and assistant create students and search them by name or document. Students are never deleted. */
export default function StudentsPage() {
  const [text, setText] = useState('');
  const [filters, setFilters] = useState({ q: '', page: 1 });
  const students = useLoad(() => searchStudents({ ...filters, size: SIZE }), [filters.q, filters.page]);

  const [fullName, setFullName] = useState('');
  const [document, setDocument] = useState('');
  const [createError, setCreateError] = useState(null);
  const [created, setCreated] = useState(null);
  const [busy, setBusy] = useState(false);

  function search(event) {
    event.preventDefault();
    setFilters({ q: text.trim(), page: 1 });
  }

  async function create(event) {
    event.preventDefault();
    setBusy(true);
    setCreateError(null);
    setCreated(null);
    try {
      const student = await createStudent(fullName, document);
      setCreated(student);
      setFullName('');
      setDocument('');
      students.reload();
    } catch (err) {
      setCreateError(err);
    } finally {
      setBusy(false);
    }
  }

  return (
    <section>
      <h1>Estudiantes</h1>

      <details className="panel" open={false}>
        <summary>Registrar estudiante</summary>
        <form className="form-grid" onSubmit={create}>
          <ErrorMessage error={createError} />
          <label>
            Nombre completo
            <input value={fullName} onChange={(e) => setFullName(e.target.value)} required />
          </label>
          <label>
            Documento
            <input value={document} onChange={(e) => setDocument(e.target.value)} inputMode="numeric" required />
          </label>
          <div className="actions">
            <button type="submit" disabled={busy}>
              Registrar
            </button>
          </div>
        </form>
      </details>
      {created && (
        <p className="alert alert-ok">
          Se registró a <Link to={`/students/${created.id}`}>{created.full_name}</Link>.{' '}
          <Link to={`/lend?student=${created.id}`}>Prestarle un portátil</Link>
        </p>
      )}

      <form className="toolbar" onSubmit={search} role="search">
        <input
          type="search"
          placeholder="Buscar por nombre o documento"
          value={text}
          onChange={(e) => setText(e.target.value)}
          aria-label="Buscar por nombre o documento"
        />
        <button type="submit">Buscar</button>
      </form>

      <ErrorMessage error={students.error} />
      {students.loading && !students.data && <p className="muted">Cargando…</p>}
      {students.data && (
        <>
          {students.data.items.length === 0 ? (
            <p className="empty">No hay estudiantes que coincidan con la búsqueda.</p>
          ) : (
            <div className="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Nombre</th>
                    <th>Documento</th>
                    <th aria-label="Acciones" />
                  </tr>
                </thead>
                <tbody>
                  {students.data.items.map((student) => (
                    <tr key={student.id}>
                      <td>
                        <Link to={`/students/${student.id}`}>{student.full_name}</Link>
                      </td>
                      <td>{student.document_number}</td>
                      <td className="right">
                        <Link to={`/lend?student=${student.id}`}>Prestar</Link>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
          <Pagination
            page={students.data.page}
            size={students.data.size}
            total={students.data.total}
            onChange={(page) => setFilters((current) => ({ ...current, page }))}
          />
        </>
      )}
    </section>
  );
}
