import { http } from './http';
import { Aprendiz, AprendizFormData } from '../types/aprendiz';

export const aprendicesApi = {
  list: (search = '') => http.get<Aprendiz[]>(`/aprendices${search ? `?search=${encodeURIComponent(search)}` : ''}`),
  create: (data: AprendizFormData) => http.post<Aprendiz>('/aprendices', data),
  update: (id: string, data: AprendizFormData) => http.put<Aprendiz>(`/aprendices/${id}`, data),
  remove: (id: string) => http.delete<null>(`/aprendices/${id}`),
};
