# ADR-005 · El reloj es un puerto y el sembrador puede desplazarlo

- **Estado:** aceptada
- **Contexto:** el vencimiento es a 15 días (RN-2) y los vencidos bloquean nuevos préstamos (RN-3). Probar eso esperando 15 días es
  imposible, y el sembrador de `tool` debe dejar **3 préstamos vencidos** sin escribir SQL (T-21).
- **Decisión:** el dominio y la aplicación nunca llaman a `now()`: piden la hora al puerto `Clock`. En pruebas se usa `FixedClock`.
  En el contenedor se usa `SystemClock`, que suma `CLOCK_OFFSET_DAYS` días (por defecto `0`). El sembrador levanta la API una vez con
  `CLOCK_OFFSET_DAYS=-20`, registra por la API los préstamos que deben quedar vencidos, y la vuelve a levantar con `0`.
- **Alternativas descartadas:** aceptar `loaned_at` en `POST /loans` (rompe CA-06.7 y abre una puerta para falsear fechas); insertar
  préstamos viejos por SQL (prohibido para `tool`); un endpoint de demostración (sería un endpoint fuera del contrato).
- **Consecuencias:** `CLOCK_OFFSET_DAYS` distinto de 0 solo se usa al sembrar; en producción no se define. Los instantes siguen siendo UTC.
- **Cómo se verifica:** `tests/Unit/Domain/LoanTest.php` usa un reloj fijo para pasar de `open` a `overdue`; tras correr el sembrador,
  `GET /loans/overdue` devuelve 3 líneas.
