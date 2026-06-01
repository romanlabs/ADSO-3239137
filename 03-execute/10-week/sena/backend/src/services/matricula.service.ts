import { FilterQuery, Types } from 'mongoose';
import { AprendizModel } from '../entities/Aprendiz';
import { IMatricula, MatriculaModel } from '../entities/Matricula';
import { CreateMatriculaDto, MatriculaQueryDto, UpdateMatriculaDto } from '../dtos/matricula.dto';
import { AppError } from '../utils/app-error';

const populateAprendiz = 'nombre documento programa';

export class MatriculaService {
  async getAll(query: MatriculaQueryDto) {
    const filters: FilterQuery<IMatricula> = {};

    if (query.aprendizId) {
      filters.aprendizId = query.aprendizId;
    }

    if (query.ficha) {
      filters.ficha = { $regex: query.ficha, $options: 'i' };
    }

    if (query.estado) {
      filters.estado = query.estado;
    }

    if (query.search) {
      filters.$or = [
        { ficha: { $regex: query.search, $options: 'i' } },
        { jornada: { $regex: query.search, $options: 'i' } },
        { estado: { $regex: query.search, $options: 'i' } },
      ];
    }

    return MatriculaModel.find(filters)
      .populate('aprendizId', populateAprendiz)
      .sort({ createdAt: -1 });
  }

  async getById(id: string) {
    const matricula = await MatriculaModel.findById(id).populate('aprendizId', populateAprendiz);

    if (!matricula) {
      throw new AppError('Matricula no encontrada', 404);
    }

    return matricula;
  }

  async getByAprendiz(aprendizId: string) {
    await this.ensureAprendizExists(aprendizId);

    return MatriculaModel.find({ aprendizId })
      .populate('aprendizId', populateAprendiz)
      .sort({ createdAt: -1 });
  }

  async create(payload: CreateMatriculaDto) {
    await this.ensureAprendizExists(payload.aprendizId);

    const matricula = await MatriculaModel.create({
      ...payload,
      aprendizId: new Types.ObjectId(payload.aprendizId),
    });

    return matricula.populate('aprendizId', populateAprendiz);
  }

  async update(id: string, payload: UpdateMatriculaDto) {
    if (payload.aprendizId) {
      await this.ensureAprendizExists(payload.aprendizId);
    }

    const updatePayload = {
      ...payload,
      aprendizId: payload.aprendizId ? new Types.ObjectId(payload.aprendizId) : undefined,
    };

    const matricula = await MatriculaModel.findByIdAndUpdate(id, updatePayload, {
      new: true,
      runValidators: true,
    }).populate('aprendizId', populateAprendiz);

    if (!matricula) {
      throw new AppError('Matricula no encontrada', 404);
    }

    return matricula;
  }

  async delete(id: string) {
    const matricula = await MatriculaModel.findByIdAndDelete(id);

    if (!matricula) {
      throw new AppError('Matricula no encontrada', 404);
    }

    return matricula;
  }

  private async ensureAprendizExists(aprendizId: string) {
    const exists = await AprendizModel.exists({ _id: aprendizId });

    if (!exists) {
      throw new AppError('El aprendiz asociado no existe', 404);
    }
  }
}
