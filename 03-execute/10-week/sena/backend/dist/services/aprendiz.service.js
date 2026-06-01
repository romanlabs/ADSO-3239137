"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.AprendizService = void 0;
const Aprendiz_1 = require("../entities/Aprendiz");
const app_error_1 = require("../utils/app-error");
class AprendizService {
    async getAll(query) {
        const filters = {};
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
        return Aprendiz_1.AprendizModel.find(filters).sort({ createdAt: -1 });
    }
    async getById(id) {
        const aprendiz = await Aprendiz_1.AprendizModel.findById(id);
        if (!aprendiz) {
            throw new app_error_1.AppError('Aprendiz no encontrado', 404);
        }
        return aprendiz;
    }
    async create(payload) {
        return Aprendiz_1.AprendizModel.create(payload);
    }
    async update(id, payload) {
        const aprendiz = await Aprendiz_1.AprendizModel.findByIdAndUpdate(id, payload, {
            new: true,
            runValidators: true,
        });
        if (!aprendiz) {
            throw new app_error_1.AppError('Aprendiz no encontrado', 404);
        }
        return aprendiz;
    }
    async delete(id) {
        const aprendiz = await Aprendiz_1.AprendizModel.findByIdAndDelete(id);
        if (!aprendiz) {
            throw new app_error_1.AppError('Aprendiz no encontrado', 404);
        }
        return aprendiz;
    }
}
exports.AprendizService = AprendizService;
//# sourceMappingURL=aprendiz.service.js.map