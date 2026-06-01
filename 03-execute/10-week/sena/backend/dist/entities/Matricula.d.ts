import { Document, Types } from 'mongoose';
export interface IMatricula extends Document {
    aprendizId: Types.ObjectId;
    ficha: string;
    jornada: 'Diurna' | 'Nocturna' | 'Mixta';
    estado: 'En formacion' | 'Condicionado' | 'Cancelado';
    fechaInicio: Date;
    fechaFin: Date;
    createdAt: Date;
    updatedAt: Date;
}
export declare const MatriculaModel: import("mongoose").Model<IMatricula, {}, {}, {}, Document<unknown, {}, IMatricula, {}, {}> & IMatricula & Required<{
    _id: Types.ObjectId;
}> & {
    __v: number;
}, any>;
//# sourceMappingURL=Matricula.d.ts.map