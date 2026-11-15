# ADR-001 · El esquema es propiedad de las migraciones de Laravel

- **Estado:** aceptada
- **Contexto:** `infra` levanta PostgreSQL 16. Sería fácil poner el DDL en un script de `docker-entrypoint-initdb.d` y, además, tener
  migraciones en Laravel; con dos dueños, nadie sabe qué esquema corre de verdad.
- **Decisión:** **todo el DDL vive en `api/database/migrations/`** (esquema `laptops`, tablas, índices, restricciones y semilla de las
  cinco categorías). `infra` solo crea el motor vacío, el usuario, la base y el volumen. El contenedor de la API corre
  `php artisan migrate --force` al arrancar. Las migraciones por defecto de Laravel (`users`, `cache`, `jobs`) se eliminan.
- **Alternativas descartadas:** scripts SQL en `infra/` (segunda fuente de verdad); `php artisan schema:load` desde un volcado (no deja
  historial legible); crear tablas desde modelos al arrancar (no hay reversa).
- **Consecuencias:** un índice nuevo o un `CHECK` nuevo se crea en una migración nueva, nunca a mano en el motor. Las restricciones que
  el constructor de esquemas de Laravel no expresa (índice parcial, `CHECK`) se escriben con `DB::statement` dentro de la migración.
  Laravel deja una tabla técnica `migrations`; se documenta en `data-model.md` §2 y no se usa desde el código.
- **Cómo se verifica:** `grep -ri "create table" infra/` no devuelve nada; las consultas de `data-model.md` §10 coinciden con las migraciones.
