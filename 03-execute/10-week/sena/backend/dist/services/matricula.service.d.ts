import { Types } from 'mongoose';
import { IMatricula } from '../entities/Matricula';
import { CreateMatriculaDto, MatriculaQueryDto, UpdateMatriculaDto } from '../dtos/matricula.dto';
export declare class MatriculaService {
    getAll(query: MatriculaQueryDto): Promise<(import("mongoose").Document<unknown, {}, IMatricula, {}, {}> & IMatricula & Required<{
        _id: Types.ObjectId;
    }> & {
        __v: number;
    })[]>;
    getById(id: string): Promise<import("mongoose").Document<unknown, {}, IMatricula, {}, {}> & IMatricula & Required<{
        _id: Types.ObjectId;
    }> & {
        __v: number;
    }>;
    getByAprendiz(aprendizId: string): Promise<(import("mongoose").Document<unknown, {}, IMatricula, {}, {}> & IMatricula & Required<{
        _id: Types.ObjectId;
    }> & {
        __v: number;
    })[]>;
    create(payload: CreateMatriculaDto): Promise<Omit<import("mongoose").Document<unknown, {}, IMatricula, {}, {}> & IMatricula & Required<{
        _id: Types.ObjectId;
    }> & {
        __v: number;
    }, never>>;
    update(id: string, payload: UpdateMatriculaDto): Promise<import("mongoose").Document<unknown, {}, IMatricula, {}, {}> & IMatricula & Required<{
        _id: Types.ObjectId;
    }> & {
        __v: number;
    }>;
    delete(id: string): Promise<import("mongoose").Document<unknown, {}, IMatricula, {}, {}> & IMatricula & Required<{
        _id: Types.ObjectId;
    }> & {
        __v: number;
    }>;
    private ensureAprendizExists;
}
//# sourceMappingURL=matricula.service.d.ts.map