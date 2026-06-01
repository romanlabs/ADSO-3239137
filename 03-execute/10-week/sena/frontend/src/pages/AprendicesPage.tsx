import { FormEvent, useEffect, useMemo, useState } from 'react';
import { aprendicesApi } from '../api/aprendices.api';
import { ConfirmDialog } from '../components/ConfirmDialog';
import { FormInput } from '../components/FormField';
import { Toast } from '../components/Toast';
import { Aprendiz, AprendizFormData } from '../types/aprendiz';

const emptyForm: AprendizFormData = {
  documento: '',
  nombre: '',
  email: '',
  telefono: '',
  programa: '',
};

export function AprendicesPage() {
  const [aprendices, setAprendices] = useState<Aprendiz[]>([]);
  const [form, setForm] = useState<AprendizFormData>(emptyForm);
  const [editing, setEditing] = useState<Aprendiz | null>(null);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(false);
  const [toast, setToast] = useState<{ type: 'success' | 'error'; message: string } | null>(null);
  const [pendingDelete, setPendingDelete] = useState<Aprendiz | null>(null);

  const filtered = useMemo(() => {
    const term = search.toLowerCase();
    return aprendices.filter((item) =>
      [item.documento, item.nombre, item.email, item.telefono, item.programa].some((value) =>
        value.toLowerCase().includes(term),
      ),
    );
  }, [aprendices, search]);

  const loadData = async () => {
    setLoading(true);
    try {
      setAprendices(await aprendicesApi.list());
    } catch (error) {
      showToast('error', error instanceof Error ? error.message : 'Error al cargar aprendices');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, []);

  const showToast = (type: 'success' | 'error', message: string) => {
    setToast({ type, message });
    window.setTimeout(() => setToast(null), 3000);
  };

  const handleSubmit = async (event: FormEvent) => {
    event.preventDefault();
    setLoading(true);
    try {
      if (editing) {
        await aprendicesApi.update(editing._id, form);
        showToast('success', 'Aprendiz actualizado');
      } else {
        await aprendicesApi.create(form);
        showToast('success', 'Aprendiz creado');
      }
      setForm(emptyForm);
      setEditing(null);
      await loadData();
    } catch (error) {
      showToast('error', error instanceof Error ? error.message : 'Error al guardar aprendiz');
    } finally {
      setLoading(false);
    }
  };

  const startEdit = (aprendiz: Aprendiz) => {
    setEditing(aprendiz);
    setForm({
      documento: aprendiz.documento,
      nombre: aprendiz.nombre,
      email: aprendiz.email,
      telefono: aprendiz.telefono,
      programa: aprendiz.programa,
    });
  };

  const remove = async () => {
    if (!pendingDelete) return;
    try {
      await aprendicesApi.remove(pendingDelete._id);
      showToast('success', 'Aprendiz eliminado');
      setPendingDelete(null);
      await loadData();
    } catch (error) {
      showToast('error', error instanceof Error ? error.message : 'Error al eliminar aprendiz');
    }
  };

  return (
    <section className="page split-page">
      {toast && <Toast {...toast} />}
      <div className="page-header">
        <div>
          <h2>Gestión de Aprendices</h2>
        </div>
        <input
          className="search-input"
          placeholder="Buscar aprendiz"
          value={search}
          onChange={(event) => setSearch(event.target.value)}
        />
      </div>

      <section className="panel form-panel">
        <h2>{editing ? 'Editar aprendiz' : 'Crear aprendiz'}</h2>
        <form className="form-grid" onSubmit={handleSubmit}>
          <FormInput label="Documento" value={form.documento} onChange={(event) => setForm({ ...form, documento: event.target.value })} required />
          <FormInput label="Nombre" value={form.nombre} onChange={(event) => setForm({ ...form, nombre: event.target.value })} required />
          <FormInput label="Email" type="email" value={form.email} onChange={(event) => setForm({ ...form, email: event.target.value })} required />
          <FormInput label="Telefono" value={form.telefono} onChange={(event) => setForm({ ...form, telefono: event.target.value })} required />
          <FormInput label="Programa" value={form.programa} onChange={(event) => setForm({ ...form, programa: event.target.value })} required />
          <div className="form-actions">
            {editing && (
              <button className="btn btn-ghost" type="button" onClick={() => { setEditing(null); setForm(emptyForm); }}>
                Cancelar
              </button>
            )}
            <button className="btn btn-primary" type="submit" disabled={loading}>
              {editing ? 'Actualizar' : 'Crear'}
            </button>
          </div>
        </form>
      </section>

      <section className="panel">
        <h2>Listado</h2>
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Documento</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Telefono</th>
                <th>Programa</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              {filtered.map((aprendiz) => (
                <tr key={aprendiz._id}>
                  <td>{aprendiz.documento}</td>
                  <td>{aprendiz.nombre}</td>
                  <td>{aprendiz.email}</td>
                  <td>{aprendiz.telefono}</td>
                  <td>{aprendiz.programa}</td>
                  <td className="actions">
                    <button className="btn btn-small" type="button" onClick={() => startEdit(aprendiz)}>Editar</button>
                    <button className="btn btn-small btn-danger" type="button" onClick={() => setPendingDelete(aprendiz)}>Eliminar</button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>

      {pendingDelete && (
        <ConfirmDialog
          title="Eliminar aprendiz"
          description={`Se eliminara a ${pendingDelete.nombre}.`}
          onCancel={() => setPendingDelete(null)}
          onConfirm={remove}
        />
      )}
    </section>
  );
}
