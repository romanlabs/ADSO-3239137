# ADR-003 · Los portátiles se dan de baja, no se borran

- **Estado:** aceptada
- **Contexto:** una línea de préstamo apunta a un portátil. Si el portátil se borra físicamente, el historial y el reporte quedan rotos.
- **Decisión:** el portátil lleva `deleted_at`. Dar de baja lo llena con el instante del reloj; todas las consultas del inventario filtran
  por `deleted_at IS NULL` dentro del repositorio (un solo lugar). Un portátil con préstamos abiertos **no** se da de baja
  (409 `LAPTOP_HAS_OPEN_LOANS`). La unicidad del código de modelo es un índice **parcial** sobre activos, así un código dado de baja
  puede volver a registrarse.
- **Alternativas descartadas:** borrado físico con cascada (destruye el histórico); columna `active` booleana (no dice cuándo);
  `SoftDeletes` de Eloquent (ata el dominio al ORM y añade el modelo de Eloquent que no usamos).
- **Consecuencias:** `loan_item.laptop_id` lleva una FK `RESTRICT` como barrera de última instancia. El binario de la foto es el único
  dato que sí se borra físicamente, después de anular `photo_key`.
- **Cómo se verifica:** consultar un portátil dado de baja devuelve 404; un `DELETE` por `psql` sobre un portátil con líneas falla por la FK.
