# Arquitectura Del Sistema

La solucion usa una arquitectura cliente-servidor con tres servicios Docker:

- `sena-frontend`: aplicacion React.
- `sena-backend`: API REST Express.
- `sena-db`: base de datos MongoDB.

## Backend

Capas principales:

- `routes`: define endpoints HTTP.
- `controllers`: recibe solicitudes y devuelve respuestas.
- `services`: contiene reglas de negocio.
- `entities`: modelos Mongoose.
- `validators`: valida datos de entrada.
- `middlewares`: logging, errores y validacion de identificadores.
- `config`: variables de entorno y conexion a MongoDB.

## Frontend

Capas principales:

- `api`: clientes HTTP.
- `types`: modelos TypeScript.
- `components`: piezas reutilizables.
- `pages`: pantallas de dashboard, aprendices y matriculas.
