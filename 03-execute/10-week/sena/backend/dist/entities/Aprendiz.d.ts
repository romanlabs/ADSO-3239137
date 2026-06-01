import { Document } from 'mongoose';
export interface IAprendiz extends Document {
    documento: string;
    nombre: string;
    email: string;
    telefono: string;
    programa: string;
    createdAt: Date;
    updatedAt: Date;
}
export declare const AprendizModel: import("mongoose").Model<IAprendiz, {}, {}, {}, Document<unknown, {}, IAprendiz, {}, {}> & IAprendiz & Required<{
    _id: import("mongoose").Types.ObjectId;
}> & {
    __v: number;
}, any>;
//# sourceMappingURL=Aprendiz.d.ts.map