import { CreateAprendizDto, UpdateAprendizDto } from '../dtos/aprendiz.dto';
import { AppError } from '../utils/app-error';

const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const onlyDigitsRegex = /^\d+$/;

function requireText(value: unknown, field: string) {
  if (typeof value !== 'string' || value.trim().length === 0) {
    throw new AppError(`El campo ${field} es obligatorio`, 400);
  }

  return value.trim();
}

export function validateCreateAprendiz(payload: Record<string, unknown>): CreateAprendizDto {
  const documento = requireText(payload.documento, 'documento');
  const nombre = requireText(payload.nombre, 'nombre');
  const email = requireText(payload.email, 'email').toLowerCase();
  const telefono = requireText(payload.telefono, 'telefono');
  const programa = requireText(payload.programa, 'programa');

  if (!onlyDigitsRegex.test(documento)) {
    throw new AppError('El documento debe contener solo numeros', 400);
  }

  if (!emailRegex.test(email)) {
    throw new AppError('El email no tiene un formato valido', 400);
  }

  if (!onlyDigitsRegex.test(telefono) || telefono.length < 7) {
    throw new AppError('El telefono debe contener al menos 7 numeros', 400);
  }

  return { documento, nombre, email, telefono, programa };
}

export function validateUpdateAprendiz(payload: Record<string, unknown>): UpdateAprendizDto {
  const data: UpdateAprendizDto = {};

  if (payload.documento !== undefined) data.documento = requireText(payload.documento, 'documento');
  if (payload.nombre !== undefined) data.nombre = requireText(payload.nombre, 'nombre');
  if (payload.email !== undefined) data.email = requireText(payload.email, 'email').toLowerCase();
  if (payload.telefono !== undefined) data.telefono = requireText(payload.telefono, 'telefono');
  if (payload.programa !== undefined) data.programa = requireText(payload.programa, 'programa');

  if (data.documento && !onlyDigitsRegex.test(data.documento)) {
    throw new AppError('El documento debe contener solo numeros', 400);
  }

  if (data.email && !emailRegex.test(data.email)) {
    throw new AppError('El email no tiene un formato valido', 400);
  }

  if (data.telefono && (!onlyDigitsRegex.test(data.telefono) || data.telefono.length < 7)) {
    throw new AppError('El telefono debe contener al menos 7 numeros', 400);
  }

  if (Object.keys(data).length === 0) {
    throw new AppError('Debe enviar al menos un campo para actualizar', 400);
  }

  return data;
}
