"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.validateCreateAprendiz = validateCreateAprendiz;
exports.validateUpdateAprendiz = validateUpdateAprendiz;
const app_error_1 = require("../utils/app-error");
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const onlyDigitsRegex = /^\d+$/;
function requireText(value, field) {
    if (typeof value !== 'string' || value.trim().length === 0) {
        throw new app_error_1.AppError(`El campo ${field} es obligatorio`, 400);
    }
    return value.trim();
}
function validateCreateAprendiz(payload) {
    const documento = requireText(payload.documento, 'documento');
    const nombre = requireText(payload.nombre, 'nombre');
    const email = requireText(payload.email, 'email').toLowerCase();
    const telefono = requireText(payload.telefono, 'telefono');
    const programa = requireText(payload.programa, 'programa');
    if (!onlyDigitsRegex.test(documento)) {
        throw new app_error_1.AppError('El documento debe contener solo numeros', 400);
    }
    if (!emailRegex.test(email)) {
        throw new app_error_1.AppError('El email no tiene un formato valido', 400);
    }
    if (!onlyDigitsRegex.test(telefono) || telefono.length < 7) {
        throw new app_error_1.AppError('El telefono debe contener al menos 7 numeros', 400);
    }
    return { documento, nombre, email, telefono, programa };
}
function validateUpdateAprendiz(payload) {
    const data = {};
    if (payload.documento !== undefined)
        data.documento = requireText(payload.documento, 'documento');
    if (payload.nombre !== undefined)
        data.nombre = requireText(payload.nombre, 'nombre');
    if (payload.email !== undefined)
        data.email = requireText(payload.email, 'email').toLowerCase();
    if (payload.telefono !== undefined)
        data.telefono = requireText(payload.telefono, 'telefono');
    if (payload.programa !== undefined)
        data.programa = requireText(payload.programa, 'programa');
    if (data.documento && !onlyDigitsRegex.test(data.documento)) {
        throw new app_error_1.AppError('El documento debe contener solo numeros', 400);
    }
    if (data.email && !emailRegex.test(data.email)) {
        throw new app_error_1.AppError('El email no tiene un formato valido', 400);
    }
    if (data.telefono && (!onlyDigitsRegex.test(data.telefono) || data.telefono.length < 7)) {
        throw new app_error_1.AppError('El telefono debe contener al menos 7 numeros', 400);
    }
    if (Object.keys(data).length === 0) {
        throw new app_error_1.AppError('Debe enviar al menos un campo para actualizar', 400);
    }
    return data;
}
//# sourceMappingURL=aprendiz.validator.js.map