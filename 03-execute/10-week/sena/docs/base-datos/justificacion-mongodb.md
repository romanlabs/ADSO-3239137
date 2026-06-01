# Justificacion De MongoDB

MongoDB es adecuado para este proyecto porque permite trabajar con documentos flexibles, indices y validaciones de esquema sin perder agilidad de desarrollo.

El modelo usa colecciones separadas para `aprendices` y `matriculas`, evitando duplicar datos personales en cada matricula. La relacion se implementa mediante referencia con `ObjectId`, y Mongoose permite aplicar reglas de tipo, enumeraciones, campos obligatorios y timestamps.

Para una aplicacion academica de gestion, MongoDB facilita consultas rapidas por documento, nombre, programa, ficha y estado. Ademas, el uso de `$lookup` permite obtener reportes equivalentes a consultas relacionales con `JOIN`.
