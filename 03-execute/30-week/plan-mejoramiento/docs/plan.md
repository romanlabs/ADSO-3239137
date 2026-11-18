# Plan técnico — Préstamo de portátiles (Laravel + React)

Cómo se construye lo que dicen [`spec.md`](spec.md) y [`constitution.md`](constitution.md). El esquema **no** se describe aquí: vive en
[`data-model.md`](data-model.md). La forma del sistema, en [`architecture.md`](architecture.md).

## 1. Decisiones técnicas

| # | Decisión | Elección | Por qué |
|---|---|---|---|
| D-01 | Lenguaje y runtime del backend | PHP 8.4 | Requisito del plan: backend en PHP |
| D-02 | Framework HTTP | Laravel 12, solo como capa de entrada (rutas, middleware, validación, contenedor) | Contrato en [`api-contract.md`](api-contract.md) |
| D-03 | Persistencia | Constructor de consultas de Laravel (`DB::table`) sobre PostgreSQL 16, en adaptadores; migraciones de Laravel | El esquema es de las migraciones ([ADR-001](adr/adr-001-propiedad-del-esquema.md)); el dominio no hereda de `Model` |
| D-04 | Concurrencia | Actualización condicional sobre `available_units` (comparar y fijar) + `CHECK` de rango ([ADR-002](adr/adr-002-concurrencia-optimista.md)) | Última unidad (RNF-2) |
| D-05 | Fechas | Instantes UTC (`timestamptz`); el reloj es un puerto (`Clock`) | Probar vencimientos sin esperar 15 días ([ADR-005](adr/adr-005-reloj-como-puerto.md)) |
| D-06 | Reporte | Una consulta agregada por un puerto de lectura ([ADR-004](adr/adr-004-reporte-agregado-y-congelado.md)) | Estable y barato |
| D-07 | Objetos de valor | `ModelCode`, `Quantity`, `DocumentNumber`, `DateRange`: viven dentro de la fila de su dueño, sin tabla | No tienen identidad |
| D-08 | Fotos | Binario en almacenamiento externo (disco local en `storage/app/photos`; S3 en producción); la base guarda solo `photo_key` | Clave opaca, nunca ruta |
| D-09 | Contraseñas | Argon2id (`password_hash` de PHP) detrás del puerto `PasswordHasher`; el dominio nunca ve la clave en claro | Artículo IX |
| D-10 | Arranque | Comando `php artisan app:bootstrap-admin` crea el admin inicial desde `ADMIN_USERNAME` / `ADMIN_PASSWORD` | La base no puede producir el hash |
| D-11 | Autenticación | JWT HS256 (`firebase/php-jwt`), 8 horas, clave en `JWT_SECRET`. **Sin Sanctum ni sesiones**: no crea tablas | Simple, sin estado y sin tablas del framework |
| D-12 | Pruebas | PHPUnit 11: unitarias con fakes para los puertos; feature contra PostgreSQL real del contenedor | Pirámide de pruebas |
| D-13 | Frontend | React 18 + Vite + React Router; `fetch` nativo con el token Bearer | Requisito del plan: frontend en React |
| D-14 | Sitio público | HTML y CSS estáticos, sin JavaScript de datos ni llamadas a la API | Se abre sin backend |
| D-15 | Contenedores | Docker Compose con tres servicios: `db`, `api`, `app` | Un solo comando (RNF-5) |
| D-16 | Sembrador | Node 20 con `fetch`, contra `/api/v1` | Siembra por la API, nunca SQL |

Lo que Laravel trae y **no** se usa: Eloquent en el dominio, Sanctum, sesiones en base, caché en base, colas, vistas Blade, la
migración por defecto de `users`. Se eliminan para no crear tablas que el modelo de datos no declara.

## 2. Configuración por entorno

| Variable | Obligatoria | Ejemplo |
|---|---|---|
| `DB_HOST` / `DB_PORT` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | sí | `db` / `5432` / `laptops` / `laptops` / definida en `.env` |
| `DB_SCHEMA` | sí | `laptops` |
| `APP_KEY` | sí | generada con `php artisan key:generate --show`, nunca en el repo |
| `JWT_SECRET` | sí | cadena larga aleatoria |
| `ADMIN_USERNAME` / `ADMIN_PASSWORD` | sí | definidos en `.env`, nunca en el repo |
| `PHOTOS_DIR` | no | `storage/app/photos` |
| `CORS_ORIGIN` | sí | `http://localhost:5173` |
| `CLOCK_OFFSET_DAYS` | no | `0` (solo distinto de 0 al sembrar datos de demostración; ver ADR-005) |
| `VITE_API_URL` (app) | sí | `http://localhost:8000` (origen de la API; el cliente le suma `/api/v1`. Se fija al compilar) |

## 3. Estrategia de pruebas

| Nivel | Qué prueba | Contra qué | Carpeta |
|---|---|---|---|
| Dominio | Invariantes: máximo 1, vencimiento a 15 días, disponibles en rango | Nada: objetos puros | `api/tests/Unit/Domain` |
| Aplicación | Casos de uso: prestar, devolver, reporte, login | Fakes de los puertos | `api/tests/Unit/Application` |
| Integración | Repositorios y consulta del reporte | PostgreSQL real | `api/tests/Feature` |
| Contrato | Cuerpo de error único y códigos de estado de todas las rutas | La API dentro de la prueba | `api/tests/Feature` |
| Arquitectura | Artículos I, II y III | El código fuente | `api/tests/Architecture` |
| Concurrencia | Dos préstamos de la última unidad | PostgreSQL real, dos conexiones | `api/tests/Feature` |

Se corren con `docker compose exec api php vendor/bin/phpunit` desde `infra/`; `phpunit.xml` fija la base `laptops_test` y `tests/TestCase.php` se niega a correr sobre otra.

## 4. Orden de construcción

Esqueleto y salud → migración inicial con semilla → dominio → casos de uso con fakes → adaptadores → API → pruebas de contrato y
concurrencia → infra → sembrador → app → sitio. El detalle, con evidencia, está en [`tasks.md`](tasks.md).
