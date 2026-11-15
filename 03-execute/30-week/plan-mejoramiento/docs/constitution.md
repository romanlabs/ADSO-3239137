# Constitución — Préstamo de portátiles (Laravel + React)

Principios innegociables del proyecto. Cada artículo dice **cómo se verifica**: un principio que nadie puede comprobar es solo una
intención. Un cambio que viole un artículo se rechaza sin discutir el resto.

Stack: backend en **PHP 8.4 con Laravel 12**, frontend en **React 18 con Vite**, base **PostgreSQL 16**. Las rutas de carpetas que
aparecen abajo son las del repositorio `api/`.

---

## Artículo I — El dominio no depende de nada

`app/Domain/` no importa ningún otro espacio de nombres del proyecto ni del framework: solo PHP estándar (`readonly`, `DateTimeImmutable`,
enumeraciones). Ni `Illuminate\…`, ni Eloquent, ni facades, ni helpers de Laravel.

**Por qué:** es lo único que permite probar las invariantes sin base de datos, sin servidor y sin contenedor, y cambiar la persistencia
sin tocar reglas.
**Verificación:** `tests/Architecture/DomainIsIsolatedTest.php` recorre `app/Domain` y falla si encuentra `use Illuminate\`,
`use App\Application\` o `use App\Infrastructure\`.

## Artículo II — El núcleo declara sus puertos; el mundo los implementa

La capa de aplicación (`app/Application/`) solo conoce el dominio. Todo lo que necesita del exterior (repositorios, reloj, hash de claves,
almacenamiento de fotos, emisión de tokens, transacciones) lo expresa como una **interfaz que ella misma define** en
`app/Application/Ports/`. Ningún tipo de Laravel aparece en la firma de un puerto.

**Verificación:** `tests/Architecture/ApplicationDependsOnPortsTest.php` falla si `app/Application` importa `App\Infrastructure\` o
`Illuminate\`. Las pruebas de aplicación corren con fakes en `tests/Fakes/` y sin base de datos.

## Artículo III — Un único punto de composición

Un solo proveedor de servicios, `app/Providers/PortBindingsServiceProvider.php`, es donde un puerto se encuentra con su adaptador
(`$this->app->bind(Port::class, Adapter::class)`). Fuera de él no se instancia infraestructura con `new`.

**Verificación:** `tests/Architecture/SingleCompositionRootTest.php` falla si aparece `new …Repository`, `new …Hasher`,
`new …Storage` o `new …Issuer` de `App\Infrastructure` en otro archivo.

## Artículo IV — SOLID no es decorativo

| Principio | Aplicación concreta | Verificación |
|---|---|---|
| **SRP** | Un servicio de aplicación = un caso de uso. `LendLaptopService` presta y no hace nada más | Un servicio con dos razones para cambiar se parte |
| **OCP** | Cambiar el almacenamiento de fotos (disco → S3) no toca dominio ni aplicación | El adaptador nuevo solo cambia una línea en el proveedor de composición |
| **LSP** | Los fakes de prueba sustituyen al adaptador real sin cambiar expectativas | Las mismas reglas pasan contra el fake (unitarias) y contra PostgreSQL (feature) |
| **ISP** | Puertos pequeños: `Clock`, `PasswordHasher`, `TokenIssuer`, `PhotoStorage`, `TransactionRunner` | Ningún adaptador deja métodos vacíos |
| **DIP** | El puerto lo define quien lo consume | Los puertos viven en `app/Application/Ports/` |

## Artículo V — El esquema es propiedad de las migraciones

El repositorio `infra` levanta un motor **vacío**. Todo el DDL (esquema, tablas, índices, restricciones y semilla de categorías) vive en
las migraciones de Laravel (`api/database/migrations/`). Ver [ADR-001](adr/adr-001-propiedad-del-esquema.md).
**Verificación:** `infra/` no contiene `CREATE TABLE` ni scripts en `docker-entrypoint-initdb.d`.

## Artículo VI — Las invariantes que la base puede imponer, la base las impone

Una regla que solo vive en código protege a la aplicación, no a los datos. Toda invariante expresable en el motor (`CHECK`, `UNIQUE`, FK)
se declara ahí. Lo que todavía no lo está se marca **pendiente** en [`data-model.md`](data-model.md); no se promete.
**Verificación:** las consultas de `data-model.md` §10 coinciden con lo que declara el documento.

## Artículo VII — Lo derivado se calcula, no se almacena

El estado de un préstamo (`open`, `overdue`, `closed`) y los días de retraso se calculan. No hay columna para ellos.
**Verificación:** `data-model.md` no lista ninguna columna `status` ni `days_late`.

## Artículo VIII — Los valores congelados no siguen al inventario

La línea de préstamo guarda nombre del portátil, código de modelo y categoría **del instante del préstamo**. Ningún reporte los lee del
inventario vivo.
**Verificación:** una prueba renombra un portátil después de prestarlo y comprueba que el reporte conserva el nombre viejo
([ADR-004](adr/adr-004-reporte-agregado-y-congelado.md)).

## Artículo IX — Ningún secreto entra al repositorio ni a un log

Ni contraseñas, ni hashes precalculados, ni `JWT_SECRET`, ni `APP_KEY`. Todo secreto viene del entorno. Los `.env` reales están en
`.gitignore`; solo se versiona `.env.example`.
**Verificación:** `git ls-files | grep -E '(^|/)\.env$'` no devuelve nada; ninguna respuesta HTTP incluye `password` ni `password_hash`
(`tests/Feature/PasswordNeverLeaksTest.php`).

## Artículo X — Gana el motor

Si el documento de datos y la base discrepan, el documento está roto y se corrige el documento.

## Artículo XI — Idioma

Código, identificadores, comentarios, columnas y rutas en **inglés**. Documentación y mensajes al usuario en **español**.
**Verificación:** revisión de código; los textos en español solo aparecen en cadenas que ve el usuario.

## Artículo XII — Nada se da por hecho sin evidencia

Una tarea de [`tasks.md`](tasks.md) está terminada cuando su **evidencia** existe y se puede ejecutar, no cuando «ya está programada».
