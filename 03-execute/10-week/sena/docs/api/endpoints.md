# Endpoints API

Base URL: `http://localhost:3001/api`

## Aprendices

| Metodo | Ruta | Descripcion |
| --- | --- | --- |
| GET | `/aprendices` | Lista aprendices |
| GET | `/aprendices/:id` | Obtiene aprendiz |
| POST | `/aprendices` | Crea aprendiz |
| PUT | `/aprendices/:id` | Actualiza aprendiz |
| DELETE | `/aprendices/:id` | Elimina aprendiz |

## Matriculas

| Metodo | Ruta | Descripcion |
| --- | --- | --- |
| GET | `/matriculas` | Lista matriculas |
| GET | `/matriculas/:id` | Obtiene matricula |
| GET | `/matriculas/aprendiz/:aprendizId` | Lista matriculas de un aprendiz |
| POST | `/matriculas` | Crea matricula |
| PUT | `/matriculas/:id` | Actualiza matricula |
| DELETE | `/matriculas/:id` | Elimina matricula |
