# docs — Especificación del préstamo de portátiles

La especificación manda: el código de `api/`, `app/`, `page/`, `infra/` y `tool/` se construye a partir de estos documentos y se corrige
el documento cuando la realidad lo contradice (artículo X).

## Cómo se lee, en este orden

1. [`constitution.md`](constitution.md) — principios innegociables y cómo se verifica cada uno.
2. [`spec.md`](spec.md) — actores, historias HU-01 a HU-10, criterios de aceptación, reglas RN-1 a RN-6 y requisitos RNF-1 a RNF-5.
3. [`plan.md`](plan.md), [`architecture.md`](architecture.md) y [`arquitectura-panoramica.md`](arquitectura-panoramica.md) — cómo se
   construye con Laravel 12, React y PostgreSQL 16.
4. [`data-model.md`](data-model.md) y [`api-contract.md`](api-contract.md) — el modelo de datos y el contrato HTTP.
5. [`tasks.md`](tasks.md) — las 23 tareas y la evidencia con que se da cada una por hecha.
6. [`adr/`](adr/) — las decisiones de arquitectura, con sus alternativas y consecuencias.

## Decisiones registradas

| ADR | Decisión |
|---|---|
| [ADR-001](adr/adr-001-propiedad-del-esquema.md) | El esquema es propiedad de las migraciones de Laravel |
| [ADR-002](adr/adr-002-concurrencia-optimista.md) | Concurrencia optimista sobre las unidades disponibles |
| [ADR-003](adr/adr-003-baja-logica.md) | Los portátiles se dan de baja, no se borran |
| [ADR-004](adr/adr-004-reporte-agregado-y-congelado.md) | El reporte se calcula en el motor y agrupa por valores congelados |
| [ADR-005](adr/adr-005-reloj-como-puerto.md) | El reloj es un puerto y el sembrador puede desplazarlo |
