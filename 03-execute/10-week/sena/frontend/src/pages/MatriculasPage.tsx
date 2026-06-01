import { FormEvent, useEffect, useMemo, useState } from 'react';
import { aprendicesApi } from '../api/aprendices.api';
import { matriculasApi } from '../api/matriculas.api';
import { ConfirmDialog } from '../components/ConfirmDialog';
import { FormInput, FormSelect } from '../components/FormField';
import { Toast } from '../components/Toast';
import { Aprendiz } from '../types/aprendiz';
import { Matricula, MatriculaFormData } from '../types/matricula';
import { statusClassName } from '../utils/format';

const emptyForm: MatriculaFormData = {
  aprendizId: '',
  ficha: '',
  jornada: 'Diurna',
  estado: 'En formacion',
  fechaInicio: '',
  fechaFin: '',
};

export function MatriculasPage() {
  const [matriculas, setMatriculas] = useState<Matricula[]>([]);
  const [aprendices, setAprendices] = useState<Aprendiz[]>([]);
  const [form, setForm] = useState<MatriculaFormData>(emptyForm);
  const [editing, setEditing] = useState<Matricula | null>(null);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(false);
  const [toast, setToast] = useState<{ type: 'success' | 'error'; message: string } | null>(null);
  const [pendingDelete, setPendingDelete] = useState<Matricula | null>(null);

  const filtered = useMemo(() => {
    const term = search.toLowerCase();
    return matriculas.filter((item) => {
      const aprendiz = typeof item.aprendizId === 'string' ? null : item.aprendizId;
      return [item.ficha, item.jornada, item.estado, aprendiz?.nombre || '', aprendiz?.documento || ''].some((value) =>
        value.toLowerCase().includes(term),
      );
    });
  }, [matriculas, search]);

  const loadData = async () => {
    setLoading(true);
    try {
      const [aprendicesData, matriculasData] = await Promise.all([
        aprendicesApi.list(),
        matriculasApi.list(),
      ]);
      setAprendices(aprendicesData);
      setMatriculas(matriculasData);
    } catch (error) {
      showToast('error', error instanceof Error ? error.message : 'Error al cargar matriculas');
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
        await matriculasApi.update(editing._id, form);
        showToast('success', 'Matricula actualizada');
      } else {
        await matriculasApi.create(form);
        showToast('success', 'Matricula creada');
      }
      setForm(emptyForm);
      setEditing(null);
      await loadData();
    } catch (error) {
      showToast('error', error instanceof Error ? error.message : 'Error al guardar matricula');
    } finally {
      setLoading(false);
    }
  };

  const startEdit = (matricula: Matricula) => {
    const aprendizId = typeof matricula.aprendizId === 'string' ? matricula.aprendizId : matricula.aprendizId._id;
    setEditing(matricula);
    setForm({
      aprendizId,
      ficha: matricula.ficha,
      jornada: matricula.jornada,
      estado: matricula.estado,
      fechaInicio: matricula.fechaInicio.slice(0, 10),
      fechaFin: matricula.fechaFin.slice(0, 10),
    });
  };

  const remove = async () => {
    if (!pendingDelete) return;
    try {
      await matriculasApi.remove(pendingDelete._id);
      showToast('success', 'Matricula eliminada');
      setPendingDelete(null);
      await loadData();
    } catch (error) {
      showToast('error', error instanceof Error ? error.message : 'Error al eliminar matricula');
    }
  };

  return (
    <section className="page split-page">
      {toast && <Toast {...toast} />}
      <div className="page-header">
        <div>
          <h2>Gestión de Matrículas</h2>
        </div>
        <input
          className="search-input"
          placeholder="Buscar matricula"
          value={search}
          onChange={(event) => setSearch(event.target.value)}
        />
      </div>

      <section className="panel form-panel">
        <h2>{editing ? 'Editar matricula' : 'Crear matricula'}</h2>
        <form className="form-grid" onSubmit={handleSubmit}>
          <FormSelect
            label="Aprendiz"
            value={form.aprendizId}
            onChange={(event) => setForm({ ...form, aprendizId: event.target.value })}
            required
            options={[
              { label: 'Seleccione un aprendiz', value: '' },
              ...aprendices.map((aprendiz) => ({
                label: `${aprendiz.documento} - ${aprendiz.nombre}`,
                value: aprendiz._id,
              })),
            ]}
          />
          <FormInput label="Ficha" value={form.ficha} onChange={(event) => setForm({ ...form, ficha: event.target.value })} required />
          <FormSelect
            label="Jornada"
            value={form.jornada}
            onChange={(event) => setForm({ ...form, jornada: event.target.value as MatriculaFormData['jornada'] })}
            options={[
              { label: 'Diurna', value: 'Diurna' },
              { label: 'Nocturna', value: 'Nocturna' },
              { label: 'Mixta', value: 'Mixta' },
            ]}
          />
          <FormSelect
            label="Estado"
            value={form.estado}
            onChange={(event) => setForm({ ...form, estado: event.target.value as MatriculaFormData['estado'] })}
            options={[
              { label: 'En formacion', value: 'En formacion' },
              { label: 'Condicionado', value: 'Condicionado' },
              { label: 'Cancelado', value: 'Cancelado' },
            ]}
          />
          <FormInput label="Fecha inicio" type="date" value={form.fechaInicio} onChange={(event) => setForm({ ...form, fechaInicio: event.target.value })} required />
          <FormInput label="Fecha fin" type="date" value={form.fechaFin} onChange={(event) => setForm({ ...form, fechaFin: event.target.value })} required />
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
                <th>Aprendiz</th>
                <th>Ficha</th>
                <th>Jornada</th>
                <th>Estado</th>
                <th>Inicio</th>
                <th>Fin</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              {filtered.map((matricula) => {
                const aprendiz = typeof matricula.aprendizId === 'string' ? null : matricula.aprendizId;
                return (
                  <tr key={matricula._id}>
                    <td>{aprendiz?.nombre || 'Sin aprendiz'}</td>
                    <td>{matricula.ficha}</td>
                    <td>{matricula.jornada}</td>
                    <td><span className={`badge ${statusClassName(matricula.estado)}`}>{matricula.estado}</span></td>
                    <td>{matricula.fechaInicio.slice(0, 10)}</td>
                    <td>{matricula.fechaFin.slice(0, 10)}</td>
                    <td className="actions">
                      <button className="btn btn-small" type="button" onClick={() => startEdit(matricula)}>Editar</button>
                      <button className="btn btn-small btn-danger" type="button" onClick={() => setPendingDelete(matricula)}>Eliminar</button>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </section>

      {pendingDelete && (
        <ConfirmDialog
          title="Eliminar matricula"
          description={`Se eliminara la ficha ${pendingDelete.ficha}.`}
          onCancel={() => setPendingDelete(null)}
          onConfirm={remove}
        />
      )}
    </section>
  );
}
