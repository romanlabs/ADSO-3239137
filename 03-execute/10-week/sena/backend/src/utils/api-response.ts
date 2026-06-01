export function successResponse<T>(data: T, message = 'Operacion exitosa') {
  return {
    success: true,
    message,
    data,
  };
}
