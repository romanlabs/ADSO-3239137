import { ErrorRequestHandler } from 'express';
import mongoose from 'mongoose';
import { AppError } from '../utils/app-error';

export const errorHandler: ErrorRequestHandler = (error, _req, res, _next) => {
  console.error(error);

  if (error instanceof AppError) {
    res.status(error.statusCode).json({
      success: false,
      error: error.message,
      details: error.details,
    });
    return;
  }

  if (error instanceof mongoose.Error.ValidationError) {
    res.status(400).json({
      success: false,
      error: 'Datos invalidos',
      details: Object.values(error.errors).map((item) => item.message),
    });
    return;
  }

  if (error instanceof mongoose.Error.CastError) {
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
