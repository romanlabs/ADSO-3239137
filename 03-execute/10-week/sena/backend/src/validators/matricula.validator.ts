import { Types } from 'mongoose';
import {
  CreateMatriculaDto,
  EstadoMatricula,
  Jornada,
  UpdateMatriculaDto,
} from '../dtos/matricula.dto';
import { AppError } from '../utils/app-error';

const jornadas: Jornada[] = ['Diurna', 'Nocturna', 'Mixta'];
const estados: EstadoMatricula[] = ['En formacion', 'Condicionado', 'Cancelado'];

function requireText(value: unknown, field: string) {
  if (typeof value !== 'string' || value.trim().length === 0) {
    throw new AppError(`El campo ${field} es obligatorio`, 400);
  }

  return value.trim();
}

function parseDate(value: unknown, field: string) {
  const rawValue = requireText(value, field);
  const date = new Date(rawValue);

  if (Number.isNaN(date.getTime())) {
    throw new AppError(`El campo ${field} debe ser una fecha valida`, 400);
  }

  return rawValue;
}

function validateDateRange(fechaInicio?: string, fechaFin?: string) {
  if (!fechaInicio || !fechaFin) return;

  if (new Date(fechaFin) < new Date(fechaInicio)) {
    throw new AppError('La fecha de fin no puede ser anterior a la fecha de inicio', 400);
  }
}

export function validateCreateMatricula(payload: Record<string, unknown>): CreateMatriculaDto {
  const aprendizId = requireText(payload.aprendizId, 'aprendizId');
  const ficha = requireText(payload.ficha, 'ficha');
  const jornada = requireText(payload.jornada, 'jornada') as Jornada;
  const estado = payload.estado ? (requireText(payload.estado, 'estado') as EstadoMatricula) : undefined;
  const fechaInicio = parseDate(payload.fechaInicio, 'fechaInicio');
  const fechaFin = parseDate(payload.fechaFin, 'fechaFin');

  if (!Types.ObjectId.isValid(aprendizId)) {
    throw new AppError('El aprendizId no es valido', 400);
  }

  if (!jornadas.includes(jornada)) {
    throw new AppError('La jornada debe ser Diurna, Nocturna o Mixta', 400);
  }

  if (estado && !estados.includes(estado)) {
    throw new AppError('El estado debe ser En formacion, Condicionado o Cancelado', 400);
  }

  validateDateRange(fechaInicio, fechaFin);

  return { aprendizId, ficha, jornada, estado, fechaInicio, fechaFin };
}

export function validateUpdateMatricula(payload: Record<string, unknown>): UpdateMatriculaDto {
  const data: UpdateMatriculaDto = {};

  if (payload.aprendizId !== undefined) data.aprendizId = requireText(payload.aprendizId, 'aprendizId');
  if (payload.ficha !== undefined) data.ficha = requireText(payload.ficha, 'ficha');
  if (payload.jornada !== undefined) data.jornada = requireText(payload.jornada, 'jornada') as Jornada;
  if (payload.estado !== undefined) data.estado = requireText(payload.estado, 'estado') as EstadoMatricula;
  if (payload.fechaInicio !== undefined) data.fechaInicio = parseDate(payload.fechaInicio, 'fechaInicio');
  if (payload.fechaFin !== undefined) data.fechaFin = parseDate(payload.fechaFin, 'fechaFin');

  if (data.aprendizId && !Types.ObjectId.isValid(data.aprendizId)) {
    throw new AppError('El aprendizId no es valido', 400);
  }

  if (data.jornada && !jornadas.includes(data.jornada)) {
    throw new AppError('La jornada debe ser Diurna, Nocturna o Mixta', 400);
  }

  if (data.estado && !estados.includes(data.estado)) {
    throw new AppError('El estado debe ser En formacion, Condicionado o Cancelado', 400);
  }

  validateDateRange(data.fechaInicio, data.fechaFin);

  if (Object.keys(data).length === 0) {
    throw new AppError('Debe enviar al menos un campo para actualizar', 400);
  }

  return data;
}
