"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.MatriculaController = void 0;
const matricula_service_1 = require("../services/matricula.service");
const api_response_1 = require("../utils/api-response");
const matricula_validator_1 = require("../validators/matricula.validator");
class MatriculaController {
    service = new matricula_service_1.MatriculaService();
    getAll = async (req, res) => {
        const matriculas = await this.service.getAll(req.query);
        res.json((0, api_response_1.successResponse)(matriculas, 'Matriculas obtenidas correctamente'));
    };
    getById = async (req, res) => {
        const matricula = await this.service.getById(String(req.params.id));
        res.json((0, api_response_1.successResponse)(matricula, 'Matricula obtenida correctamente'));
    };
    getByAprendiz = async (req, res) => {
        const matriculas = await this.service.getByAprendiz(String(req.params.aprendizId));
        res.json((0, api_response_1.successResponse)(matriculas, 'Matriculas del aprendiz obtenidas correctamente'));
    };
    create = async (req, res) => {
        const payload = (0, matricula_validator_1.validateCreateMatricula)(req.body);
        const matricula = await this.service.create(payload);
        res.status(201).json((0, api_response_1.successResponse)(matricula, 'Matricula creada correctamente'));
    };
    update = async (req, res) => {
        const payload = (0, matricula_validator_1.validateUpdateMatricula)(req.body);
        const matricula = await this.service.update(String(req.params.id), payload);
        res.json((0, api_response_1.successResponse)(matricula, 'Matricula actualizada correctamente'));
    };
    delete = async (req, res) => {
        await this.service.delete(String(req.params.id));
        res.json((0, api_response_1.successResponse)(null, 'Matricula eliminada correctamente'));
    };
}
exports.MatriculaController = MatriculaController;
//# sourceMappingURL=matricula.controller.js.map