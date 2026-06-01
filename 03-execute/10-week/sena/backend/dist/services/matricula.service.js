"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.MatriculaService = void 0;
const mongoose_1 = require("mongoose");
const Aprendiz_1 = require("../entities/Aprendiz");
const Matricula_1 = require("../entities/Matricula");
const app_error_1 = require("../utils/app-error");
const populateAprendiz = 'nombre documento programa';
class MatriculaService {
    async getAll(query) {
        const filters = {};
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
        return Matricula_1.MatriculaModel.find(filters)
            .populate('aprendizId', populateAprendiz)
            .sort({ createdAt: -1 });
    }
    async getById(id) {
        const matricula = await Matricula_1.MatriculaModel.findById(id).populate('aprendizId', populateAprendiz);
        if (!matricula) {
            throw new app_error_1.AppError('Matricula no encontrada', 404);
        }
        return matricula;
    }
    async getByAprendiz(aprendizId) {
        await this.ensureAprendizExists(aprendizId);
        return Matricula_1.MatriculaModel.find({ aprendizId })
            .populate('aprendizId', populateAprendiz)
            .sort({ createdAt: -1 });
    }
    async create(payload) {
        await this.ensureAprendizExists(payload.aprendizId);
        const matricula = await Matricula_1.MatriculaModel.create({
            ...payload,
            aprendizId: new mongoose_1.Types.ObjectId(payload.aprendizId),
        });
        return matricula.populate('aprendizId', populateAprendiz);
    }
    async update(id, payload) {
        if (payload.aprendizId) {
            await this.ensureAprendizExists(payload.aprendizId);
        }
        const updatePayload = {
            ...payload,
            aprendizId: payload.aprendizId ? new mongoose_1.Types.ObjectId(payload.aprendizId) : undefined,
        };
        const matricula = await Matricula_1.MatriculaModel.findByIdAndUpdate(id, updatePayload, {
            new: true,
            runValidators: true,
        }).populate('aprendizId', populateAprendiz);
        if (!matricula) {
            throw new app_error_1.AppError('Matricula no encontrada', 404);
        }
        return matricula;
    }
    async delete(id) {
        const matricula = await Matricula_1.MatriculaModel.findByIdAndDelete(id);
        if (!matricula) {
            throw new app_error_1.AppError('Matricula no encontrada', 404);
        }
        return matricula;
    }
    async ensureAprendizExists(aprendizId) {
        const exists = await Aprendiz_1.AprendizModel.exists({ _id: aprendizId });
        if (!exists) {
            throw new app_error_1.AppError('El aprendiz asociado no existe', 404);
        }
    }
}
exports.MatriculaService = MatriculaService;
//# sourceMappingURL=matricula.service.js.map