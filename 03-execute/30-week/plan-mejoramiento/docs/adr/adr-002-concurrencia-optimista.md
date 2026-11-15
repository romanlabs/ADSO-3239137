# ADR-002 · Concurrencia optimista sobre las unidades disponibles

- **Estado:** aceptada
- **Contexto:** dos auxiliares pueden entregar la **última unidad** del mismo portátil al mismo tiempo (RNF-2). Si ambos leen
  `available_units = 1` y ambos escriben `0`, el sistema presta un equipo que no tiene.
- **Decisión:** control **optimista** de tipo «comparar y fijar». El valor leído de `available_units` es el testigo: el adaptador escribe
  `UPDATE laptops.laptop SET available_units = :nuevo WHERE id = :id AND available_units = :leido`. Si actualiza **0 filas**, otro
  operador ganó: la transacción se revierte y el servidor responde **409 `LAPTOP_NOT_AVAILABLE`**. La **última barrera** es el
  `CHECK` `ck_laptop_available_range` (`0 <= available_units <= total_units`).
- **Alternativas descartadas:** bloqueo pesimista (`SELECT … FOR UPDATE`) en cada préstamo: serializa todo el inventario por un caso
  raro; columna `version` aparte: el modelo de datos no la declara y `available_units` ya cumple ese papel; contador en memoria: no
  sobrevive a más de un proceso PHP.
- **Consecuencias:** si el `CHECK` salta, **algo escribió fuera del adaptador**: es un defecto, no un caso de negocio. La misma
  comparación protege el cambio de total. La devolución (`restock`) **no** compara con el valor leído: suma con un incremento atómico
  (`SET available_units = available_units + 1 WHERE id = :id AND available_units < total_units`), porque una devolución no debe
  fallar solo porque otro préstamo del mismo portátil se cruzó; el `CHECK` sigue siendo la barrera final. Devolver dos veces la
  misma línea no suma dos veces porque `returned_at` ya está lleno (CA-07.2) y la actualización de la línea también es condicional
  (`WHERE returned_at IS NULL`).
- **Cómo se verifica:** `tests/Feature/LastUnitConcurrencyTest.php` abre dos conexiones sobre un portátil con una unidad: exactamente un
  préstamo queda registrado y el otro recibe `LAPTOP_NOT_AVAILABLE`.
