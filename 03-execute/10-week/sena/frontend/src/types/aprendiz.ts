export interface Aprendiz {
  _id: string;
  documento: string;
  nombre: string;
  email: string;
  telefono: string;
  programa: string;
  createdAt?: string;
  updatedAt?: string;
}

export type AprendizFormData = Omit<Aprendiz, '_id' | 'createdAt' | 'updatedAt'>;
