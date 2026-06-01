# Evidencias De Pruebas

## Backend

Comando ejecutado:

```bash
npm run build
```

Resultado: compilacion TypeScript exitosa.

## Frontend

Comando ejecutado:

```bash
npm run build
```

Resultado: compilacion TypeScript y build Vite exitosos.

## Pruebas funcionales esperadas

- `GET /` responde mensaje de API.
- `GET /health` responde estado `ok`.
- `GET /api/aprendices` lista aprendices.
- `POST /api/aprendices` crea aprendiz.
- `POST /api/matriculas` crea matricula si el aprendiz existe.
- `GET /api/matriculas` lista matriculas con datos del aprendiz.
