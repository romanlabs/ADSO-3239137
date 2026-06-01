"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.AprendizController = void 0;
const aprendiz_service_1 = require("../services/aprendiz.service");
const api_response_1 = require("../utils/api-response");
const aprendiz_validator_1 = require("../validators/aprendiz.validator");
class AprendizController {
    service = new aprendiz_service_1.AprendizService();
    getAll = async (req, res) => {
        const aprendices = await this.service.getAll(req.query);
        res.json((0, api_response_1.successResponse)(aprendices, 'Aprendices obtenidos correctamente'));
    };
    getById = async (req, res) => {
        const aprendiz = await this.service.getById(String(req.params.id));
        res.json((0, api_response_1.successResponse)(aprendiz, 'Aprendiz obtenido correctamente'));
    };
    create = async (req, res) => {
        const payload = (0, aprendiz_validator_1.validateCreateAprendiz)(req.body);
        const aprendiz = await this.service.create(payload);
        res.status(201).json((0, api_response_1.successResponse)(aprendiz, 'Aprendiz creado correctamente'));
    };
    update = async (req, res) => {
        const payload = (0, aprendiz_validator_1.validateUpdateAprendiz)(req.body);
        const aprendiz = await this.service.update(String(req.params.id), payload);
        res.json((0, api_response_1.successResponse)(aprendiz, 'Aprendiz actualizado correctamente'));
    };
    delete = async (req, res) => {
        await this.service.delete(String(req.params.id));
        res.json((0, api_response_1.successResponse)(null, 'Aprendiz eliminado correctamente'));
    };
}
exports.AprendizController = AprendizController;
//# sourceMappingURL=aprendiz.controller.js.map