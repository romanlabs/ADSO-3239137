import { useEffect, useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { getLaptop, getStudent, lendLaptop, searchLaptops, searchStudents } from '../api/client.js';
import ErrorMessage from '../components/ErrorMessage.jsx';

/**
 * HU-06. Pick one student and one laptop, then POST /loans. The rules (limit of 1, overdue, no units left) are decided
 * by the API; this page only shows its answer. Both choices can come preselected from ?student= and ?laptop=.
 */
export default function LendPage() {
  const navigate = useNavigate();
  const [params] = useSearchParams();
  const [student, setStudent] = useState(null);
  const [laptop, setLaptop] = useState(null);
  const [error, setError] = useState(null);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    const studentId = params.get('student');
    const laptopId = params.get('laptop');
    if (studentId) getStudent(studentId).then(setStudent).catch(setError);
    if (laptopId) getLaptop(laptopId).then(setLaptop).catch(setError);
  }, [params]);

  async function lend() {
    setBusy(true);
    setError(null);
    try {
      const loan = await lendLaptop(student.id, laptop.id);
      navigate(`/loans/${loan.id}`, { state: { justCreated: true } });
    } catch (err) {
      setError(err);
      // Units may have changed (another assistant lent the last one, RNF-2): refresh the chosen laptop.
      getLaptop(laptop.id).then(setLaptop).catch(() => {});
    } finally {
      setBusy(false);
    }
  }

  return (
    <section>
      <h1>Prestar un portátil</h1>
      <p className="muted">Cada estudiante puede tener un solo portátil sin devolver. El plazo es de 15 días.</p>

      <div className="two-columns">
        <Picker
          title="1. Estudiante"
          placeholder="Nombre o documento"
          chosen={student}
          onChoose={setStudent}
          search={(q) => searchStudents({ q, size: 8 })}
          render={(s) => (
            <>
              <strong>{s.full_name}</strong>
              <span className="muted">Documento {s.document_number}</span>
              {s.active_loans !== undefined && (
                <span className={s.active_loans > 0 ? 'units units-out' : 'units'}>
                  {s.active_loans > 0 ? 'Tiene un portátil sin devolver' : 'Sin portátiles pendientes'}
                </span>
              )}
            </>
          )}
          onExpand={(s) => getStudent(s.id).then(setStudent).catch(() => {})}
        />
        <Picker
          title="2. Portátil"
          placeholder="Nombre o marca"
          chosen={laptop}
          onChoose={setLaptop}
          search={(q) => searchLaptops({ q, size: 8 })}
          render={(l) => (
            <>
              <strong>{l.name}</strong>
              <span className="muted">
                {l.brand} · <code>{l.model_code}</code> · {l.category.name}
              </span>
              <span className={l.available_units === 0 ? 'units units-out' : 'units'}>
                {l.available_units} de {l.total_units} disponibles
              </span>
            </>
          )}
        />
      </div>

      <ErrorMessage error={error} />
      <div className="panel actions">
        <button type="button" disabled={!student || !laptop || busy} onClick={lend}>
          {busy ? 'Registrando…' : 'Registrar préstamo'}
        </button>
        {student && laptop && (
          <span className="muted">
            {laptop.name} para {student.full_name}
          </span>
        )}
        <Link to="/loans" className="push-right">
          Ver préstamos
        </Link>
      </div>
    </section>
  );
}

/** A small search-and-pick box used for the student and for the laptop. */
function Picker({ title, placeholder, chosen, onChoose, search, render, onExpand }) {
  const [text, setText] = useState('');
  const [results, setResults] = useState(null);
  const [error, setError] = useState(null);

  async function find(event) {
    event.preventDefault();
    setError(null);
    try {
      const page = await search(text.trim());
      setResults(page.items);
    } catch (err) {
      setError(err);
    }
  }

  function choose(item) {
    onChoose(item);
    onExpand?.(item);
    setResults(null);
    setText('');
  }

  return (
    <div className="panel picker">
      <h2>{title}</h2>
      {chosen && (
        <div className="chosen">
          <div className="stack">{render(chosen)}</div>
          <button type="button" className="ghost" onClick={() => onChoose(null)}>
            Cambiar
          </button>
        </div>
      )}
      {!chosen && (
        <>
          <form className="toolbar" onSubmit={find} role="search">
            <input
              type="search"
              value={text}
              onChange={(e) => setText(e.target.value)}
              placeholder={placeholder}
              aria-label={`Buscar ${title.toLowerCase()}`}
            />
            <button type="submit">Buscar</button>
          </form>
          <ErrorMessage error={error} />
          {results && results.length === 0 && <p className="empty">Sin resultados.</p>}
          {results && results.length > 0 && (
            <ul className="plain options">
              {results.map((item) => (
                <li key={item.id}>
                  <button type="button" className="option" onClick={() => choose(item)}>
                    <span className="stack">{render(item)}</span>
                  </button>
                </li>
              ))}
            </ul>
          )}
        </>
      )}
    </div>
  );
}
