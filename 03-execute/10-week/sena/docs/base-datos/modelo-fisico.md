# Modelo Fisico

Base de datos: `sena_matricula`

Colecciones:

- `aprendices`
- `matriculas`

Indices:

- `aprendices.documento`: unico.
- `aprendices.nombre`: busqueda.
- `aprendices.programa`: filtro por programa.
- `matriculas.aprendizId`: consulta por aprendiz.
- `matriculas.ficha`: busqueda por ficha.
- `matriculas.estado`: filtro por estado.
- `matriculas.aprendizId + ficha`: consultas combinadas.

La integridad referencial se garantiza en la capa de servicio validando que el aprendiz exista antes de crear o actualizar una matricula.
