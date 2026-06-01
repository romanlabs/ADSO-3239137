# Flujo De Datos

1. El usuario interactua con React.
2. React llama la API usando `fetch`.
3. Express recibe la solicitud en una ruta.
4. El controlador valida datos y llama al servicio.
5. El servicio aplica reglas de negocio.
6. Mongoose consulta o modifica MongoDB.
7. La API responde con un formato uniforme.
8. React actualiza tablas, formularios y mensajes.

Formato de respuesta exitosa:

```json
{
  "success": true,
  "message": "Operacion exitosa",
  "data": {}
}
```

Formato de error:

```json
{
  "success": false,
  "error": "Descripcion del error"
}
```
