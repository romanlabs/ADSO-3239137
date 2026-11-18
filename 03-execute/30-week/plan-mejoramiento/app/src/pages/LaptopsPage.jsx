import { useState } from 'react';
import { Link } from 'react-router-dom';
import { listCategories, searchLaptops } from '../api/client.js';
import { useAuth } from '../auth/AuthContext.jsx';
import ErrorMessage from '../components/ErrorMessage.jsx';
import LaptopPhoto from '../components/LaptopPhoto.jsx';
import Pagination from '../components/Pagination.jsx';
import useLoad from '../components/useLoad.js';

const SIZE = 20;

/** HU-04: search by name or brand, filter by category, active laptops only, ordered by name, paginated. */
export default function LaptopsPage() {
  const { isAdmin } = useAuth();
  const [text, setText] = useState('');
  const [filters, setFilters] = useState({ q: '', categoryId: '', page: 1 });
  const categories = useLoad(() => listCategories(), []);
  const laptops = useLoad(() => searchLaptops({ ...filters, size: SIZE }), [filters.q, filters.categoryId, filters.page]);

  function search(event) {
    event.preventDefault();
    setFilters((current) => ({ ...current, q: text.trim(), page: 1 }));
  }

  return (
    <section>
      <div className="page-head">
        <h1>Inventario</h1>
        {isAdmin && (
          <Link className="button" to="/laptops/new">
            Nuevo portátil
          </Link>
        )}
      </div>

      <form className="toolbar" onSubmit={search} role="search">
        <input
          type="search"
          placeholder="Buscar por nombre o marca"
          value={text}
          onChange={(e) => setText(e.target.value)}
          aria-label="Buscar por nombre o marca"
        />
        <select
          value={filters.categoryId}
          onChange={(e) => setFilters((current) => ({ ...current, categoryId: e.target.value, page: 1 }))}
          aria-label="Categoría"
        >
          <option value="">Todas las categorías</option>
          {(categories.data ?? []).map((category) => (
            <option key={category.id} value={category.id}>
              {category.name}
            </option>
          ))}
        </select>
        <button type="submit">Buscar</button>
      </form>

      <ErrorMessage error={laptops.error ?? categories.error} />
      {laptops.loading && !laptops.data && <p className="muted">Cargando…</p>}

      {laptops.data && (
        <>
          {laptops.data.items.length === 0 ? (
            <p className="empty">No hay portátiles que coincidan con la búsqueda.</p>
          ) : (
            <ul className="cards">
              {laptops.data.items.map((laptop) => (
                <li key={laptop.id}>
                  <Link className="card laptop-card" to={`/laptops/${laptop.id}`}>
                    <LaptopPhoto laptopId={laptop.id} photoKey={laptop.photo_key} alt={laptop.name} className="thumb" />
                    <div className="stack">
                      <strong>{laptop.name}</strong>
                      <span className="muted">
                        {laptop.brand} · <code>{laptop.model_code}</code>
                      </span>
                      <span className="muted">{laptop.category.name}</span>
                      <span className={laptop.available_units === 0 ? 'units units-out' : 'units'}>
                        {laptop.available_units} de {laptop.total_units} disponibles
                      </span>
                    </div>
                  </Link>
                </li>
              ))}
            </ul>
          )}
          <Pagination
            page={laptops.data.page}
            size={laptops.data.size}
            total={laptops.data.total}
            onChange={(page) => setFilters((current) => ({ ...current, page }))}
          />
        </>
      )}
    </section>
  );
}
