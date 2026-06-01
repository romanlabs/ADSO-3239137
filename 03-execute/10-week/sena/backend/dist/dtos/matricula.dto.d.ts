export type Jornada = 'Diurna' | 'Nocturna' | 'Mixta';
export type EstadoMatricula = 'En formacion' | 'Condicionado' | 'Cancelado';
export interface CreateMatriculaDto {
    aprendizId: string;
    ficha: string;
    jornada: Jornada;
    estado?: EstadoMatricula;
    fechaInicio: string;
    fechaFin: string;
}
export type UpdateMatriculaDto = Partial<CreateMatriculaDto>;
export interface MatriculaQueryDto {
    search?: string;
    ficha?: string;
    estado?: EstadoMatricula;
    aprendizId?: string;
}
//# sourceMappingURL=matricula.dto.d.ts.map