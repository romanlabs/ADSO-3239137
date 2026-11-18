// Display helpers. The API speaks UTC with Z (RN-6); the screen shows local time in Spanish.

const dateTime = new Intl.DateTimeFormat('es-CO', { dateStyle: 'medium', timeStyle: 'short' });
const dateOnly = new Intl.DateTimeFormat('es-CO', { dateStyle: 'medium' });

export const formatDateTime = (iso) => (iso ? dateTime.format(new Date(iso)) : '—');
export const formatDate = (iso) => (iso ? dateOnly.format(new Date(iso)) : '—');

export const STATUS_LABEL = { open: 'Abierto', overdue: 'Vencido', closed: 'Cerrado' };

/** YYYY-MM-DD of a local date, the format of the from/to filters. */
export function isoDay(date) {
  const pad = (n) => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

export function firstDayOfMonth(today = new Date()) {
  return isoDay(new Date(today.getFullYear(), today.getMonth(), 1));
}
