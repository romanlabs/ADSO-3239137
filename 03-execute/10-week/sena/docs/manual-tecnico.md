# Manual Tecnico

## Requisitos

- Docker Desktop
- Node.js 22 para desarrollo local
- NPM

## Variables de entorno

Backend:

- `PORT=3001`
- `MONGO_URI=mongodb://mongodb:27017/sena_matricula`
- `CORS_ORIGIN=*`

Frontend:

- `VITE_API_URL=http://localhost:3001/api`

## Comandos

```bash
docker compose up --build
```

```bash
cd backend
npm run build
```

```bash
cd frontend
npm run build
```

## Reglas de negocio

- El documento del aprendiz es unico.
- Toda matricula debe tener un aprendiz existente.
- La fecha final no puede ser anterior a la fecha inicial.
- La jornada solo puede ser Diurna, Nocturna o Mixta.
- El estado solo puede ser En formacion, Condicionado o Cancelado.
