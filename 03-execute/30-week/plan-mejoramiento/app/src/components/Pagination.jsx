/** Controls for a page of the contract: { page, size, total }. */
export default function Pagination({ page, size, total, onChange }) {
  const pages = Math.max(1, Math.ceil(total / size));
  return (
    <nav className="pagination" aria-label="Paginación">
      <button type="button" className="ghost" disabled={page <= 1} onClick={() => onChange(page - 1)}>
        Anterior
      </button>
      <span>
        Página {page} de {pages} · {total} {total === 1 ? 'resultado' : 'resultados'}
      </span>
      <button type="button" className="ghost" disabled={page >= pages} onClick={() => onChange(page + 1)}>
        Siguiente
      </button>
    </nav>
  );
}
