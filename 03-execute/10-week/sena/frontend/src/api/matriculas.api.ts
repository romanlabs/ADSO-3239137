import { http } from './http';
import { Matricula, MatriculaFormData } from '../types/matricula';

export const matriculasApi = {
  list: (search = '') => http.get<Matricula[]>(`/matriculas${search ? `?search=${encodeURIComponent(search)}` : ''}`),
  create: (data: MatriculaFormData) => http.post<Matricula>('/matriculas', data),
  update: (id: string, data: MatriculaFormData) => http.put<Matricula>(`/matriculas/${id}`, data),
  remove: (id: string) => http.delete<null>(`/matriculas/${id}`),
};
