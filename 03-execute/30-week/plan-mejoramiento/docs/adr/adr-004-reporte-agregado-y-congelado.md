# ADR-004 · El reporte se calcula en el motor y agrupa por valores congelados

- **Estado:** aceptada
- **Contexto:** el reporte de más solicitados (HU-10) cruza `loan` y `loan_item` sobre un rango. Si leyera el nombre o la categoría del
  inventario vivo, renombrar un portátil o una categoría cambiaría un reporte de un periodo ya cerrado.
- **Decisión:** (1) el reporte **no se persiste**: se calcula con una sola consulta agregada (`GROUP BY laptop_id, laptop_name,
  category_name`) detrás del puerto `MostRequestedReportReader`. (2) Agrupa por los valores **tal como quedaron congelados en la línea**.
  (3) `loan_item.category_name` **no tiene FK** a propósito. (4) Si un portátil cambió de categoría dentro del rango, el reporte muestra
  **dos filas**: no elige un ganador.
- **Alternativas descartadas:** unir con el inventario vivo (el reporte deja de ser estable); tomar «el valor más reciente del rango»
  (un préstamo nuevo cambiaría lo ya leído); materializar una tabla de reporte (segunda fuente de verdad, y el modelo no la declara).
- **Consecuencias:** CA-10.1 dice «una fila por portátil y nombre de categoría congelado», no «una fila por portátil». El reporte no
  incluye estudiantes ni auxiliares (privacidad, Ley 1581 de 2012).
- **Cómo se verifica:** `tests/Feature/MostRequestedReportTest.php` presta un portátil, lo renombra y pide el reporte del mismo rango:
  el nombre del reporte es el viejo.
