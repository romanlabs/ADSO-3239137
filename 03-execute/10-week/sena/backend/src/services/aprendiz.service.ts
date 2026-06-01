import { FilterQuery } from 'mongoose';
import { AprendizModel, IAprendiz } from '../entities/Aprendiz';
import { AprendizQueryDto, CreateAprendizDto, UpdateAprendizDto } from '../dtos/aprendiz.dto';
import { AppError } from '../utils/app-error';

export class AprendizService {
  async getAll(query: AprendizQueryDto) {
    const filters: FilterQuery<IAprendiz> = {};

    if (query.programa) {
      filters.programa = { $regex: query.programa, $options: 'i' };
    }

    if (query.search) {
      filters.$or = [
        { documento: { $regex: query.search, $options: 'i' } },
        { nombre: { $regex: query.search, $options: 'i' } },
        { email: { $regex: query.search, $options: 'i' } },
        { programa: { $regex: query.search, $options: 'i' } },
      ];
    }

    return AprendizModel.find(filters).sort({ createdAt: -1 });
  }

  async getById(id: string) {
    const aprendiz = await AprendizModel.findById(id);

    if (!aprendiz) {
      throw new AppError('Aprendiz no encontrado', 404);
    }

    return aprendiz;
  }

  async create(payload: CreateAprendizDto) {
    return AprendizModel.create(payload);
  }

  async update(id: string, payload: UpdateAprendizDto) {
    const aprendiz = await AprendizModel.findByIdAndUpdate(id, payload, {
      new: true,
      runValidators: true,
    });

    if (!aprendiz) {
      throw new AppError('Aprendiz no encontrado', 404);
    }

    return aprendiz;
  }

  async delete(id: string) {
    const aprendiz = await AprendizModel.findByIdAndDelete(id);

    if (!aprendiz) {
      throw new AppError('Aprendiz no encontrado', 404);
    }

    return aprendiz;
  }
}
