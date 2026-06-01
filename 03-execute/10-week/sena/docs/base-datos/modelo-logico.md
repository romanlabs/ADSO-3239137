# Modelo Logico

## Aprendiz

- `_id`: identificador unico generado por MongoDB.
- `documento`: documento unico del aprendiz.
- `nombre`: nombre completo.
- `email`: correo electronico.
- `telefono`: telefono de contacto.
- `programa`: programa de formacion.
- `createdAt`, `updatedAt`: auditoria automatica.

## Matricula

- `_id`: identificador unico generado por MongoDB.
- `aprendizId`: referencia a `Aprendiz._id`.
- `ficha`: codigo de ficha.
- `jornada`: `Diurna`, `Nocturna` o `Mixta`.
- `estado`: `En formacion`, `Condicionado` o `Cancelado`.
- `fechaInicio`: fecha inicial.
- `fechaFin`: fecha final.
- `createdAt`, `updatedAt`: auditoria automatica.
