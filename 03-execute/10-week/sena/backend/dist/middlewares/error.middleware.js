"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.errorHandler = void 0;
const mongoose_1 = __importDefault(require("mongoose"));
const app_error_1 = require("../utils/app-error");
const errorHandler = (error, _req, res, _next) => {
    console.error(error);
    if (error instanceof app_error_1.AppError) {
        res.status(error.statusCode).json({
            success: false,
            error: error.message,
            details: error.details,
        });
        return;
    }
    if (error instanceof mongoose_1.default.Error.ValidationError) {
        res.status(400).json({
            success: false,
            error: 'Datos invalidos',
            details: Object.values(error.errors).map((item) => item.message),
        });
        return;
    }
    if (error instanceof mongoose_1.default.Error.CastError) {
        res.status(400).json({
            success: false,
            error: 'Identificador invalido',
        });
        return;
    }
    if (typeof error === 'object' && error !== null && 'code' in error && error.code === 11000) {
        res.status(409).json({
            success: false,
            error: 'Ya existe un registro con un valor unico repetido',
        });
        return;
    }
    res.status(500).json({
        success: false,
        error: 'Error interno del servidor',
    });
};
exports.errorHandler = errorHandler;
//# sourceMappingURL=error.middleware.js.map