# Sembrador de datos de demostración

Llena el sistema **solo por la API** (`/api/v1`), igual que lo haría una persona desde la aplicación; nunca escribe en la base.
Crea **40 portátiles**, **10 estudiantes** y **15 préstamos**, de los cuales **3 quedan vencidos**.

## Requisitos

- Node 20 o superior (usa `fetch`, sin dependencias).
- El sistema levantado desde `../infra` con `docker compose up --build` y la base **recién creada**.

## Uso

```bash
cd tool
./seed.sh
```

`seed.sh` lee `ADMIN_USERNAME` y `ADMIN_PASSWORD` de `../infra/.env` y hace dos fases:

| Fase | Reloj de la API | Qué crea |
|---|---|---|
| `node seed.mjs past` | `CLOCK_OFFSET_DAYS=-20` | Los 40 portátiles, los 10 estudiantes, 3 préstamos que quedarán vencidos y 3 ya devueltos |
| `node seed.mjs present` | `CLOCK_OFFSET_DAYS=0` | 2 préstamos devueltos hoy y 7 abiertos |

La API decide la fecha de cada préstamo con su propio reloj, así que la única forma honesta de tener préstamos vencidos es
registrarlos con la API corriendo en el pasado ([ADR-005](../docs/adr/adr-005-reloj-como-puerto.md)). Al terminar, la API queda
otra vez con el reloj de hoy.

## Cómo se comprueba

Al final, el sembrador consulta la API e imprime los totales; si no coinciden, termina con error:

```text
Préstamos: 15 (se esperan 15).
Vencidos: 3 (se esperan 3).
Filas del reporte de más solicitados: 15.
```

Si la base ya tiene portátiles, se detiene sin tocar nada. Para empezar de cero: `docker compose down -v` en `../infra`.
