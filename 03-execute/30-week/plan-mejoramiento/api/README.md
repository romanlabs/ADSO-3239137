# api — Préstamo de portátiles (Laravel 12 + PostgreSQL 16)

Backend del sistema de préstamo de portátiles. Arquitectura hexagonal: `app/Domain` (reglas), `app/Application`
(casos de uso y puertos) e `app/Infrastructure` (adaptadores). El detalle está en
[`../docs/architecture.md`](../docs/architecture.md) y el contrato HTTP en [`../docs/api-contract.md`](../docs/api-contract.md).

## Estructura

| Carpeta | Qué hay |
|---|---|
| `app/Domain` | `Laptop`, `Loan`, `LoanItem`, `Student`, `User`, objetos de valor y errores de negocio |
| `app/Application/Ports` | Interfaces que el núcleo necesita: repositorios, reloj, hash, token, fotos, transacción |
| `app/Application/Services` | Un servicio por caso de uso (iniciar sesión, prestar, devolver, reporte, …) |
| `app/Infrastructure` | Adaptadores HTTP, PostgreSQL, seguridad, disco y reloj |
| `database/migrations` | Todo el esquema `laptops` y la semilla de las cinco categorías |
| `tests/Unit` | Dominio y casos de uso con fakes en memoria (sin base de datos) |
| `tests/Feature` | Pruebas contra PostgreSQL real y de punta a punta |
| `tests/Fakes` | Implementaciones en memoria de cada puerto |
| `tests/Support` | Piezas de prueba que usan los adaptadores reales (`PostgresApplication`, `LaptopReadHook`) |
| `app/Providers/PortBindingsServiceProvider.php` | Único lugar donde cada puerto se une con su adaptador |

## Adaptadores

| Puerto | Adaptador | Prueba contra lo real |
|---|---|---|
| Repositorios de portátil, estudiante, usuario y categoría | `Infrastructure/Persistence/Db*Repository` | `tests/Feature/RepositoriesTest.php` |
| Préstamos y reporte de más solicitados | `DbLoanRepository`, `DbMostRequestedReportReader` | `LoanRepositoryTest`, `LastUnitConcurrencyTest`, `MostRequestedReportTest` |
| Hash de claves y token | `Infrastructure/Security/Argon2PasswordHasher`, `JwtTokenIssuer` | `PasswordNeverLeaksTest` |
| Fotos | `Infrastructure/Storage/DiskPhotoStorage` | `LaptopPhotoTest` |
| Reloj, identificadores y transacción | `SystemClock`, `RandomUuidGenerator`, `DbTransactionRunner` | usados por todas las anteriores |

La carrera por la última unidad (ADR-002) se prueba con **dos conexiones reales** a PostgreSQL: un operador lee el portátil,
el otro presta y confirma en medio, y el primero recibe `LAPTOP_NOT_AVAILABLE` sin dejar préstamo a medias.

## Correr las pruebas

Con el sistema arriba (`../infra`), basta `docker compose exec api php vendor/bin/phpunit`.

Sin el compose, las pruebas de `Feature` necesitan PostgreSQL 16. Con Docker, desde esta carpeta:

```bash
docker network create laptops-dev
docker run -d --name laptops-dev-db --network laptops-dev \
  -e POSTGRES_USER=laptops -e POSTGRES_PASSWORD=devonly -e POSTGRES_DB=laptops postgres:16-alpine

docker build -t laptops-api:dev .
docker run --rm --network laptops-dev -v "$PWD:/app" -w /app \
  -e DB_HOST=laptops-dev-db -e DB_USERNAME=laptops -e DB_PASSWORD=devonly \
  --entrypoint php laptops-api:dev vendor/bin/phpunit
```

`tests/bootstrap.php` crea la base `laptops_test` si no existe. Las pruebas unitarias no la usan.

## Variables de entorno

| Variable | Para qué |
|---|---|
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Conexión a PostgreSQL |
| `JWT_SECRET` | Firma de los tokens (HS256, 8 horas) |
| `ADMIN_USERNAME`, `ADMIN_PASSWORD` | Administrador inicial que se crea al arrancar |
| `CORS_ORIGIN` | Único origen permitido para el frontend |
| `CLOCK_OFFSET_DAYS` | Desplaza el reloj solo para el sembrador ([ADR-005](../docs/adr/adr-005-reloj-como-puerto.md)); en producción no se define |

Ningún secreto se versiona: solo `.env.example`.
