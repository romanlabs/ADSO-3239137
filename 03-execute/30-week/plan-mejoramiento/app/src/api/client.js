// The only module that talks to the API (docs/architecture.md). Every route of docs/api-contract.md §3 used by the app
// lives here, under /api/v1. Pages never call fetch directly.

const ORIGIN = (import.meta.env.VITE_API_URL ?? 'http://localhost:8000').replace(/\/+$/, '');
export const API_BASE = `${ORIGIN}/api/v1`;

/** Error with the single error body of the contract: { code, message, details }. */
export class ApiError extends Error {
  constructor(status, code, message, details = []) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.code = code;
    this.details = details;
  }
}

let tokenProvider = () => null;
let unauthorizedHandler = () => {};

/** The auth module tells the client where the token is and what to do on a 401. */
export function configureClient({ getToken, onUnauthorized }) {
  tokenProvider = getToken ?? tokenProvider;
  unauthorizedHandler = onUnauthorized ?? unauthorizedHandler;
}

function query(params = {}) {
  const search = new URLSearchParams();
  for (const [key, value] of Object.entries(params)) {
    if (value !== undefined && value !== null && value !== '') search.set(key, String(value));
  }
  const text = search.toString();
  return text ? `?${text}` : '';
}

async function toApiError(response) {
  let body = null;
  try {
    body = await response.json();
  } catch {
    body = null;
  }
  if (body && typeof body.code === 'string') {
    return new ApiError(response.status, body.code, body.message ?? 'Error inesperado.', body.details ?? []);
  }
  return new ApiError(response.status, 'INTERNAL_ERROR', 'La API respondió con un error inesperado.');
}

/**
 * Sends one request. `body` is sent as JSON unless it is FormData (photo upload).
 * `as` chooses how the success body is read: 'json' (default), 'blob' or 'none'.
 */
export async function request(method, path, { body, params, as = 'json', auth = true, fetchImpl = fetch } = {}) {
  const headers = { Accept: 'application/json' };
  const token = auth ? tokenProvider() : null;
  if (token) headers.Authorization = `Bearer ${token}`;

  let payload;
  if (body instanceof FormData) {
    payload = body;
  } else if (body !== undefined) {
    headers['Content-Type'] = 'application/json';
    payload = JSON.stringify(body);
  }

  let response;
  try {
    response = await fetchImpl(`${API_BASE}${path}${query(params)}`, { method, headers, body: payload });
  } catch {
    throw new ApiError(0, 'NETWORK_ERROR', 'No se pudo conectar con la API. Revisa que esté encendida.');
  }

  if (!response.ok) {
    const error = await toApiError(response);
    if (response.status === 401 && auth) unauthorizedHandler(error);
    throw error;
  }
  if (as === 'none' || response.status === 204) return null;
  if (as === 'blob') return response.blob();
  return response.json();
}

// 3.1 Authentication and health
export const login = (username, password) =>
  request('POST', '/auth/login', { body: { username, password }, auth: false });
export const health = () => request('GET', '/health', { auth: false });

// 3.2 Users
export const createAssistant = (username, password) =>
  request('POST', '/users', { body: { username, password, role: 'assistant' } });

// 3.3 Categories
export const listCategories = () => request('GET', '/categories');

// 3.4 Laptops
export const searchLaptops = ({ q, categoryId, page = 1, size = 20 } = {}) =>
  request('GET', '/laptops', { params: { q, category_id: categoryId, page, size } });
export const getLaptop = (id) => request('GET', `/laptops/${id}`);
export const createLaptop = (laptop) => request('POST', '/laptops', { body: laptop });
export const updateLaptop = (id, laptop) => request('PUT', `/laptops/${id}`, { body: laptop });
export const retireLaptop = (id) => request('DELETE', `/laptops/${id}`, { as: 'none' });
export const fetchLaptopPhoto = (id) => request('GET', `/laptops/${id}/photo`, { as: 'blob' });
export const removeLaptopPhoto = (id) => request('DELETE', `/laptops/${id}/photo`, { as: 'none' });
export function uploadLaptopPhoto(id, file) {
  const form = new FormData();
  form.append('file', file);
  return request('PUT', `/laptops/${id}/photo`, { body: form });
}

// 3.5 Students
export const searchStudents = ({ q, page = 1, size = 20 } = {}) =>
  request('GET', '/students', { params: { q, page, size } });
export const getStudent = (id) => request('GET', `/students/${id}`);
export const createStudent = (fullName, documentNumber) =>
  request('POST', '/students', { body: { full_name: fullName, document_number: documentNumber } });

// 3.6 Loans
export const lendLaptop = (studentId, laptopId) =>
  request('POST', '/loans', { body: { student_id: studentId, items: [{ laptop_id: laptopId }] } });
export const listLoans = ({ from, to, status, studentId, page = 1, size = 20 } = {}) =>
  request('GET', '/loans', { params: { from, to, status, student_id: studentId, page, size } });
export const getLoan = (id) => request('GET', `/loans/${id}`);
export const returnLoanItem = (loanId, itemId) => request('POST', `/loans/${loanId}/items/${itemId}/return`);
export const listOverdue = () => request('GET', '/loans/overdue');

// 3.7 Report
export const mostRequested = (from, to) => request('GET', '/reports/most-requested', { params: { from, to } });
