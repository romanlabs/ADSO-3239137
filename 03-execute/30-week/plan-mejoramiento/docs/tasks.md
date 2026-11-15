# Tareas — Préstamo de portátiles (Laravel + React)

Cada tarea termina cuando su **evidencia** existe y se puede ejecutar (artículo XII). La columna **Estado** se actualiza en el mismo
commit que entrega la evidencia. Los comandos se corren desde `infra/` con los contenedores arriba.

| # | Tarea | Depende de | Evidencia | Estado |
|---|---|---|---|---|
| T-01 | Esqueleto del proyecto Laravel y `GET /api/v1/health` | — | `curl localhost:8000/api/v1/health` responde `{"status":"ok"}`; `tests/Feature/HealthTest.php` | hecha |
| T-02 | Migración inicial: esquema `laptops`, seis tablas, FK, índices únicos y las cinco categorías sembradas | T-01 | Las consultas de `data-model.md` §10 devuelven lo documentado; ninguna columna con `DEFAULT`; `tests/Feature/SchemaTest.php` | hecha |
| T-03 | Dominio: `ModelCode`, `Quantity`, `DocumentNumber`, `DateRange` (objetos de valor) | T-01 | `tests/Unit/Domain/ValueObjectsTest.php`: código de 4 a 20 caracteres, cantidad > 0, fin ≥ inicio | hecha |
| T-04 | Dominio: `Laptop` (`withdraw`, `restock`, `changeTotal`, baja lógica) | T-03 | `tests/Unit/Domain/LaptopTest.php`: nunca < 0, nunca > total | hecha |
| T-05 | Dominio: `Loan` (`addItem`, `returnItem`, estado calculado, 15 días, máx. 1) | T-04 | `tests/Unit/Domain/LoanTest.php` con un reloj fijo: `open`, `overdue`, `closed` | hecha |
| T-06 | Dominio: `Student` y `User` (normalización de nombre, rol válido) | T-03 | `tests/Unit/Domain/UserAndStudentTest.php`: `"Camila "` queda `camila`; rol `admin` por API rechazado | hecha |
| T-07 | Puertos y casos de uso de **autenticación** (`LoginService`, `CreateAssistantService`) con fakes | T-06 | `tests/Unit/Application/AuthServicesTest.php`: HU-01 y HU-02 pasan contra fakes | hecha |
| T-08 | Puertos y casos de uso de **portátiles** (crear, buscar, editar, baja, foto) con fakes | T-04 | `tests/Unit/Application/LaptopServicesTest.php`: HU-03 y HU-04 pasan contra fakes | hecha |
| T-09 | Caso de uso **prestar** (`LendLaptopService`) con fakes | T-05, T-08 | `tests/Unit/Application/LendLaptopServiceTest.php`: HU-06 completa (límite, vencidos, sin unidades, repetido) | hecha |
| T-10 | Caso de uso **devolver** y **vencidos** | T-09 | `tests/Unit/Application/ReturnAndOverdueTest.php`: HU-07 y HU-09; devolver dos veces → `ITEM_ALREADY_RETURNED` | pendiente |
| T-11 | Caso de uso **reporte de más solicitados** y su puerto de lectura | T-09 | `tests/Feature/MostRequestedReportTest.php`: HU-10; el nombre viejo se conserva tras renombrar (ADR-004) | pendiente |
| T-12 | Adaptador de persistencia: repositorios de portátil, estudiante, usuario y categoría | T-02, T-08 | `tests/Feature/RepositoriesTest.php` contra PostgreSQL real | pendiente |
| T-13 | Adaptador de persistencia: repositorio de préstamos con actualización condicional de unidades | T-12, T-09 | `tests/Feature/LastUnitConcurrencyTest.php`: dos préstamos de la última unidad, uno 201 y otro 409 (RNF-2) | pendiente |
| T-14 | Adaptador de seguridad: hash Argon2id y token JWT | T-07 | `tests/Feature/PasswordNeverLeaksTest.php`: la clave nunca aparece en logs ni respuestas (RNF-3) | pendiente |
| T-15 | Adaptador de fotos (disco) | T-08 | `tests/Feature/LaptopPhotoTest.php`: PNG de 2 MB ok; 3 MB → 422; sin foto → `photo_key` nulo | pendiente |
| T-16 | Adaptador HTTP: rutas de [`api-contract.md`](api-contract.md), cuerpo de error único, autorización por rol | T-10, T-11, T-14, T-15 | `tests/Feature/ContractTest.php` recorre las 21 rutas y los 14 códigos de §2 | pendiente |
| T-17 | Composición y arranque (admin inicial desde entorno) | T-16 | El sistema arranca con la base vacía y se puede iniciar sesión como admin; `tests/Feature/BootstrapAdminTest.php` | pendiente |
| T-18 | Bajar a la base las invariantes **solo dominio** que se pueden expresar: `ck_laptop_total_positive`, `ck_user_role` | T-12 | `tests/Feature/DatabaseChecksTest.php`: un `INSERT` que las viole falla; `data-model.md` §4 actualizado | pendiente |
| T-19 | Pruebas de arquitectura (artículos I, II y III) | T-17 | `tests/Architecture/*Test.php` pasan y fallan si se introduce una importación prohibida | pendiente |
| T-20 | `infra`: `docker compose up` levanta motor vacío, API y frontend | T-17 | Un comando; la base arranca sin tablas hasta que la API corre las migraciones | pendiente |
| T-21 | `tool`: sembrador de demostración **por la API** (≥ 40 portátiles, 10 estudiantes, 15 préstamos, 3 vencidos) | T-17 | Tras correrlo, `/loans/overdue` devuelve 3 líneas y el reporte tiene filas | pendiente |
| T-22 | `app`: login, inventario, estudiantes, prestar, devolver, vencidos, reporte, auxiliares | T-16 | Recorrido de punta a punta descrito en `app/README.md` | pendiente |
| T-23 | `page`: sitio público estático de presentación | — | Se abre sin la API; correcto en móvil (375 px) y escritorio | pendiente |

## Definición de terminado

El plan exige las **23** tareas con evidencia. T-01 a T-17 son el núcleo; T-18 a T-23 completan el producto. Una tarea con evidencia
vale más que tres a medias: no se marca `hecha` sin que su prueba o comando pase.
