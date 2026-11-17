# infra — levantar el sistema con un comando

`compose.yaml` arranca los servicios del sistema de préstamo de portátiles:

| Servicio | Imagen | Puerto | Qué hace |
|---|---|---|---|
| `db` | `postgres:16` | interno (5432) | Motor vacío: no trae scripts de inicio ni tablas |
| `api` | se construye desde `../api` | `8000` | Corre las migraciones al arrancar y sirve `/api/v1` |
| `app` | se construye desde `../app` | `5173` | Frontend React (se suma cuando la app esté lista) |

## Pasos

```bash
cd infra
cp .env.example .env        # y reemplaza cada valor
docker compose up --build --wait
curl http://localhost:8000/api/v1/health   # {"status":"ok"}
```

`.env` nunca se sube al repositorio. Si falta una variable obligatoria, `docker compose` se detiene y dice cuál.

| Variable | Para qué |
|---|---|
| `DB_PASSWORD` | Clave del usuario `laptops` en PostgreSQL |
| `APP_KEY` | Llave de Laravel (`php artisan key:generate --show`) |
| `JWT_SECRET` | Firma de los tokens; 32 caracteres o más |
| `ADMIN_USERNAME`, `ADMIN_PASSWORD` | Administrador inicial: al arrancar, la API corre `php artisan app:bootstrap-admin` y lo crea si no existe |
| `CORS_ORIGIN` | Único origen que puede llamar a la API desde el navegador |
| `CLOCK_OFFSET_DAYS` | Solo para datos de demostración; normalmente `0` |

Datos de demostración: con la base recién creada, `cd ../tool && ./seed.sh` siembra por la API 40 portátiles, 10
estudiantes, 15 préstamos y 3 vencidos ([`tool/README.md`](../tool/README.md)).

## Quién crea el esquema

La base arranca **sin tablas**. Las crean las migraciones de la API (`php artisan migrate --force` en
`api/docker/entrypoint.sh`), nunca `infra` ([ADR-001](../docs/adr/adr-001-propiedad-del-esquema.md)). Para comprobarlo:

```bash
docker compose exec db psql -U laptops -d laptops -c "\dt laptops.*"
```

## Correr las pruebas de la API

```bash
docker compose exec api php vendor/bin/phpunit
```

## Apagar

```bash
docker compose down        # conserva los datos y las fotos
docker compose down -v     # borra también los volúmenes
```
