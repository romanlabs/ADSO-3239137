import { useState } from 'react';
import ErrorMessage from './ErrorMessage.jsx';

/**
 * Create (with model code) or edit (model code is not editable, api-contract §3.4).
 * The API normalizes and validates; the form only mirrors the obvious limits (CA-03.2, CA-03.3).
 */
export default function LaptopForm({ categories, initial, editing = false, submitLabel, onSubmit }) {
  const [form, setForm] = useState({
    name: initial?.name ?? '',
    brand: initial?.brand ?? '',
    model_code: initial?.model_code ?? '',
    category_id: initial?.category?.id ?? categories[0]?.id ?? '',
    total_units: initial?.total_units ?? 1,
  });
  const [error, setError] = useState(null);
  const [busy, setBusy] = useState(false);

  const set = (field) => (event) => setForm((current) => ({ ...current, [field]: event.target.value }));

  async function submit(event) {
    event.preventDefault();
    setBusy(true);
    setError(null);
    const body = {
      name: form.name,
      brand: form.brand,
      category_id: form.category_id,
      total_units: Number(form.total_units),
    };
    if (!editing) body.model_code = form.model_code;
    try {
      await onSubmit(body);
    } catch (err) {
      setError(err);
    } finally {
      setBusy(false);
    }
  }

  return (
    <form className="panel form-grid" onSubmit={submit}>
      <ErrorMessage error={error} />
      <label>
        Nombre
        <input value={form.name} onChange={set('name')} required />
      </label>
      <label>
        Marca
        <input value={form.brand} onChange={set('brand')} required />
      </label>
      <label>
        Código de modelo
        <input
          value={form.model_code}
          onChange={set('model_code')}
          disabled={editing}
          minLength={4}
          maxLength={20}
          pattern="[A-Za-z0-9\-]{4,20}"
          title="De 4 a 20 letras, dígitos o guiones"
          required={!editing}
        />
      </label>
      <label>
        Categoría
        <select value={form.category_id} onChange={set('category_id')} required>
          {categories.map((category) => (
            <option key={category.id} value={category.id}>
              {category.name}
            </option>
          ))}
        </select>
      </label>
      <label>
        Unidades totales
        <input type="number" min={1} step={1} value={form.total_units} onChange={set('total_units')} required />
      </label>
      <div className="actions">
        <button type="submit" disabled={busy}>
          {submitLabel}
        </button>
      </div>
    </form>
  );
}
