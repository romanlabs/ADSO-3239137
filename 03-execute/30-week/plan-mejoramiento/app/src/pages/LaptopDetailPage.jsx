import { useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { getLaptop, listCategories, removeLaptopPhoto, retireLaptop, updateLaptop, uploadLaptopPhoto } from '../api/client.js';
import { useAuth } from '../auth/AuthContext.jsx';
import ErrorMessage from '../components/ErrorMessage.jsx';
import LaptopForm from '../components/LaptopForm.jsx';
import LaptopPhoto from '../components/LaptopPhoto.jsx';
import useLoad from '../components/useLoad.js';

const MAX_PHOTO_BYTES = 2 * 1024 * 1024;

/** Detail for everyone; edit, photo and retire only for the admin (HU-03). */
export default function LaptopDetailPage() {
  const { id } = useParams();
  const { isAdmin } = useAuth();
  const navigate = useNavigate();
  const laptop = useLoad(() => getLaptop(id), [id]);
  const categories = useLoad(() => (isAdmin ? listCategories() : Promise.resolve([])), [isAdmin]);
  const [current, setCurrent] = useState(null);
  const [error, setError] = useState(null);
  const [saved, setSaved] = useState('');
  const [confirmRetire, setConfirmRetire] = useState(false);
  const [busy, setBusy] = useState(false);

  const data = current?.id === id ? current : laptop.data;

  async function run(action, message) {
    setBusy(true);
    setError(null);
    setSaved('');
    try {
      const result = await action();
      if (result) setCurrent(result);
      setSaved(message);
    } catch (err) {
      setError(err);
    } finally {
      setBusy(false);
    }
  }

  function choosePhoto(event) {
    const file = event.target.files?.[0];
    event.target.value = '';
    if (!file) return;
    // The API checks the real content type and size (CA-03.4); this only avoids a useless upload.
    if (file.size > MAX_PHOTO_BYTES) {
      setSaved('');
      setError({ message: 'La foto pesa más de 2 MB.', details: [] });
      return;
    }
    run(() => uploadLaptopPhoto(id, file), 'Foto actualizada.');
  }

  function removePhoto() {
    run(async () => {
      await removeLaptopPhoto(id);
      return { ...data, photo_key: null };
    }, 'Foto quitada.');
  }

  async function retire() {
    setBusy(true);
    setError(null);
    try {
      await retireLaptop(id);
      navigate('/laptops', { replace: true });
    } catch (err) {
      setError(err);
      setConfirmRetire(false);
      setBusy(false);
    }
  }

  if (laptop.loading && !data) return <p className="muted">Cargando…</p>;
  if (laptop.error) {
    return (
      <section>
        <ErrorMessage error={laptop.error} />
        <Link to="/laptops">Volver al inventario</Link>
      </section>
    );
  }

  return (
    <section>
      <p>
        <Link to="/laptops">← Inventario</Link>
      </p>
      <div className="detail">
        <LaptopPhoto laptopId={data.id} photoKey={data.photo_key} alt={data.name} />
        <div>
          <h1>{data.name}</h1>
          <dl className="facts">
            <dt>Marca</dt>
            <dd>{data.brand}</dd>
            <dt>Código de modelo</dt>
            <dd>
              <code>{data.model_code}</code>
            </dd>
            <dt>Categoría</dt>
            <dd>{data.category.name}</dd>
            <dt>Unidades</dt>
            <dd>
              {data.available_units} disponibles de {data.total_units}
            </dd>
          </dl>
          {data.available_units > 0 && (
            <Link className="button" to={`/lend?laptop=${data.id}`}>
              Prestar este portátil
            </Link>
          )}
        </div>
      </div>

      <ErrorMessage error={error} />
      {saved && <p className="alert alert-ok">{saved}</p>}

      {isAdmin && (
        <>
          <h2>Foto</h2>
          <div className="panel actions">
            <label className="button file-button">
              {data.photo_key ? 'Cambiar foto' : 'Subir foto'}
              <input type="file" accept="image/jpeg,image/png" onChange={choosePhoto} disabled={busy} hidden />
            </label>
            {data.photo_key && (
              <button type="button" className="ghost" disabled={busy} onClick={removePhoto}>
                Quitar foto
              </button>
            )}
            <span className="muted">JPG o PNG, máximo 2 MB.</span>
          </div>

          <h2>Editar</h2>
          {categories.data && (
            <LaptopForm
              key={`${data.id}-${data.total_units}-${data.name}-${data.category.id}`}
              categories={categories.data}
              initial={data}
              editing
              submitLabel="Guardar cambios"
              onSubmit={async (body) => {
                setSaved('');
                const updated = await updateLaptop(id, body);
                setCurrent(updated);
                setSaved('Cambios guardados.');
              }}
            />
          )}

          <h2>Dar de baja</h2>
          <div className="panel actions">
            <p className="muted">
              Deja de aparecer en el inventario, pero sigue en los préstamos pasados. No se puede si tiene préstamos
              abiertos.
            </p>
            {confirmRetire ? (
              <>
                <button type="button" className="danger" disabled={busy} onClick={retire}>
                  Sí, dar de baja
                </button>
                <button type="button" className="ghost" onClick={() => setConfirmRetire(false)}>
                  Cancelar
                </button>
              </>
            ) : (
              <button type="button" className="danger" onClick={() => setConfirmRetire(true)}>
                Dar de baja
              </button>
            )}
          </div>
        </>
      )}
    </section>
  );
}
