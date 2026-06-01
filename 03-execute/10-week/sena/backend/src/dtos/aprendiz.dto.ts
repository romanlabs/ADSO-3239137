export interface CreateAprendizDto {
  documento: string;
  nombre: string;
  email: string;
  telefono: string;
  programa: string;
}

export type UpdateAprendizDto = Partial<CreateAprendizDto>;

export interface AprendizQueryDto {
  search?: string;
  programa?: string;
}
