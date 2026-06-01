"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.validateCreateMatricula = validateCreateMatricula;
exports.validateUpdateMatricula = validateUpdateMatricula;
const mongoose_1 = require("mongoose");
const app_error_1 = require("../utils/app-error");
const jornadas = ['Diurna', 'Nocturna', 'Mixta'];
const estados = ['En formacion', 'Condicionado', 'Cancelado'];
function requireText(value, field) {
    if (typeof value !== 'string' || value.trim().length === 0) {
        throw new app_error_1.AppError(`El campo ${field} es obligatorio`, 400);
    }
    return value.trim();
}
function parseDate(value, field) {
    const rawValue = requireText(value, field);
    const date = new Date(rawValue);
    if (Number.isNaN(date.getTime())) {
        throw new app_error_1.AppError(`El campo ${field} debe ser una fecha valida`, 400);
    }
    return rawValue;
}
function validateDateRange(fechaInicio, fechaFin) {
    if (!fechaInicio || !fechaFin)
        return;
    if (new Date(fechaFin) < new Date(fechaInicio)) {
        throw new app_error_1.AppError('La fecha de fin no puede ser anterior a la fecha de inicio', 400);
    }
}
function validateCreateMatricula(payload) {
    const aprendizId = requireText(payload.aprendizId, 'aprendizId');
    const ficha = requireText(payload.ficha, 'ficha');
    const jornada = requireText(payload.jornada, 'jornada');
    const estado = payload.estado ? requireText(payload.estado, 'estado') : undefined;
    const fechaInicio = parseDate(payload.fechaInicio, 'fechaInicio');
    const fechaFin = parseDate(payload.fechaFin, 'fechaFin');
    if (!mongoose_1.Types.ObjectId.isValid(aprendizId)) {
        throw new app_error_1.AppError('El aprendizId no es valido', 400);
    }
    if (!jornadas.includes(jornada)) {
        throw new app_error_1.AppError('La jornada debe ser Diurna, Nocturna o Mixta', 400);
    }
    if (estado && !estados.includes(estado)) {
        throw new app_error_1.AppError('El estado debe ser En formacion, Condicionado o Cancelado', 400);
    }
    validateDateRange(fechaInicio, fechaFin);
    return { aprendizId, ficha, jornada, estado, fechaInicio, fechaFin };
}
function validateUpdateMatricula(payload) {
    const data = {};
    if (payload.aprendizId !== undefined)
        data.aprendizId = requireText(payload.aprendizId, 'aprendizId');
    if (payload.ficha !== undefined)
        data.ficha = requireText(payload.ficha, 'ficha');
    if (payload.jornada !== undefined)
        data.jornada = requireText(payload.jornada, 'jornada');
    if (payload.estado !== undefined)
        data.estado = requireText(payload.estado, 'estado');
    if (payload.fechaInicio !== undefined)
        data.fechaInicio = parseDate(payload.fechaInicio, 'fechaInicio');
    if (payload.fechaFin !== undefined)
        data.fechaFin = parseDate(payload.fechaFin, 'fechaFin');
    if (data.aprendizId && !mongoose_1.Types.ObjectId.isValid(data.aprendizId)) {
        throw new app_error_1.AppError('El aprendizId no es valido', 400);
    }
    if (data.jornada && !jornadas.includes(data.jornada)) {
        throw new app_error_1.AppError('La jornada debe ser Diurna, Nocturna o Mixta', 400);
    }
    if (data.estado && !estados.includes(data.estado)) {
        throw new app_error_1.AppError('El estado debe ser En formacion, Condicionado o Cancelado', 400);
    }
    validateDateRange(data.fechaInicio, data.fechaFin);
    if (Object.keys(data).length === 0) {
        throw new app_error_1.AppError('Debe enviar al menos un campo para actualizar', 400);
    }
    return data;
}
//# sourceMappingURL=matricula.validator.js.map