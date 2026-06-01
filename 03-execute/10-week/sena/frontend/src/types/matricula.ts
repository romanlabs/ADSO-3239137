import { Aprendiz } from './aprendiz';

export type Jornada = 'Diurna' | 'Nocturna' | 'Mixta';
export type EstadoMatricula = 'En formacion' | 'Condicionado' | 'Cancelado';

export interface Matricula {
  _id: string;
  aprendizId: string | Pick<Aprendiz, '_id' | 'documento' | 'nombre' | 'programa'>;
  ficha: string;
  jornada: Jornada;
  estado: EstadoMatricula;
  fechaInicio: string;
  fechaFin: string;
  createdAt?: string;
  updatedAt?: string;
}

export interface MatriculaFormData {
  aprendizId: string;
  ficha: string;
  jornada: Jornada;
  estado: EstadoMatricula;
  fechaInicio: string;
  fechaFin: string;
}
