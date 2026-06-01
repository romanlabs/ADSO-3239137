import { IAprendiz } from '../entities/Aprendiz';
import { AprendizQueryDto, CreateAprendizDto, UpdateAprendizDto } from '../dtos/aprendiz.dto';
export declare class AprendizService {
    getAll(query: AprendizQueryDto): Promise<(import("mongoose").Document<unknown, {}, IAprendiz, {}, {}> & IAprendiz & Required<{
        _id: import("mongoose").Types.ObjectId;
    }> & {
        __v: number;
    })[]>;
    getById(id: string): Promise<import("mongoose").Document<unknown, {}, IAprendiz, {}, {}> & IAprendiz & Required<{
        _id: import("mongoose").Types.ObjectId;
    }> & {
        __v: number;
    }>;
    create(payload: CreateAprendizDto): Promise<import("mongoose").Document<unknown, {}, IAprendiz, {}, {}> & IAprendiz & Required<{
        _id: import("mongoose").Types.ObjectId;
    }> & {
        __v: number;
    }>;
    update(id: string, payload: UpdateAprendizDto): Promise<import("mongoose").Document<unknown, {}, IAprendiz, {}, {}> & IAprendiz & Required<{
        _id: import("mongoose").Types.ObjectId;
    }> & {
        __v: number;
    }>;
    delete(id: string): Promise<import("mongoose").Document<unknown, {}, IAprendiz, {}, {}> & IAprendiz & Required<{
        _id: import("mongoose").Types.ObjectId;
    }> & {
        __v: number;
    }>;
}
//# sourceMappingURL=aprendiz.service.d.ts.map