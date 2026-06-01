interface ConfirmDialogProps {
  title: string;
  description: string;
  onCancel: () => void;
  onConfirm: () => void;
}

export function ConfirmDialog({ title, description, onCancel, onConfirm }: ConfirmDialogProps) {
  return (
    <div className="dialog-backdrop" role="presentation">
      <section className="dialog" role="dialog" aria-modal="true" aria-labelledby="dialog-title">
        <h2 id="dialog-title">{title}</h2>
        <p>{description}</p>
        <div className="dialog-actions">
          <button className="btn btn-ghost" type="button" onClick={onCancel}>
            Cancelar
          </button>
          <button className="btn btn-danger" type="button" onClick={onConfirm}>
            Eliminar
          </button>
        </div>
      </section>
    </div>
  );
}
