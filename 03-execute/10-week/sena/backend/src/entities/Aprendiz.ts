import { Document, Schema, model } from 'mongoose';

export interface IAprendiz extends Document {
  documento: string;
  nombre: string;
  email: string;
  telefono: string;
  programa: string;
  createdAt: Date;
  updatedAt: Date;
}

const AprendizSchema = new Schema<IAprendiz>(
  {
    documento: { type: String, required: true, unique: true, trim: true },
    nombre: { type: String, required: true, trim: true },
    email: { type: String, required: true, lowercase: true, trim: true },
    telefono: { type: String, required: true, trim: true },
    programa: { type: String, required: true, trim: true },
  },
  { timestamps: true },
);

AprendizSchema.index({ nombre: 1 });
AprendizSchema.index({ programa: 1 });

export const AprendizModel = model<IAprendiz>('Aprendiz', AprendizSchema);
