Este proyecto es la solución del plan de mejoramiento del aprendiz A06, ficha 3239137, dominio laptops.

# Préstamo de portátiles a estudiantes

Sistema para que una institución lleve su inventario de portátiles, sus estudiantes y los préstamos
que hacen sus auxiliares, y vea qué portátiles se solicitan más.

| Carpeta | Contenido |
|---|---|
| [`docs/`](docs/) | Especificación: constitución, spec, plan, arquitectura, modelo de datos, contrato de la API, tareas y ADR |
| [`api/`](api/) | Backend en PHP con Laravel (arquitectura hexagonal) |
| [`app/`](app/) | Frontend en React para administradores y auxiliares |
| [`page/`](page/) | Sitio público estático que presenta el producto |
| [`infra/`](infra/) | Contenedores: PostgreSQL 16 vacío, API y app con un solo comando |
| [`tool/`](tool/) | Sembrador de datos de demostración que usa la API |

El acta del plan, con código y sin datos personales, está en [`Plan-Mejoramiento-A06.pdf`](Plan-Mejoramiento-A06.pdf).

## Cómo correrlo

Requisitos: Docker con Compose y Node 20 o superior (solo para el sembrador).

```bash
cd infra
cp .env.example .env              # reemplaza cada valor; JWT_SECRET de 32 caracteres o más
docker compose up --build --wait  # db (postgres:16), api (8000) y app (5173)
curl http://localhost:8000/api/v1/health    # {"status":"ok"}

cd ../tool
./seed.sh                         # 40 portátiles, 10 estudiantes, 15 préstamos, 3 vencidos
```

Abre `http://localhost:5173` y entra con `ADMIN_USERNAME` y `ADMIN_PASSWORD` del `.env`. El sitio público no necesita
nada de esto: se abre con doble clic en `page/index.html`.

| Qué | Cómo |
|---|---|
| Pruebas de la API | `docker compose exec api php vendor/bin/phpunit` (en `infra`) |
| Pruebas de la app | `npm install && npm test` (en `app`) |
| Ver el esquema creado por las migraciones | `docker compose exec db psql -U laptops -d laptops -c "\dt laptops.*"` |
| Empezar de cero | `docker compose down -v` (en `infra`) |

Más detalle en el README de cada carpeta y en [`docs/tasks.md`](docs/tasks.md), con la evidencia de las 23 tareas.
