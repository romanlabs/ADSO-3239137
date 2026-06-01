# Diccionario De Datos

## aprendices

| Campo | Tipo | Requerido | Restriccion | Descripcion |
| --- | --- | --- | --- | --- |
| `_id` | ObjectId | Si | PK | Identificador interno |
| `documento` | String | Si | Unico | Documento del aprendiz |
| `nombre` | String | Si | Indice | Nombre completo |
| `email` | String | Si | Formato email | Correo electronico |
| `telefono` | String | Si | Numerico | Telefono |
| `programa` | String | Si | Indice | Programa de formacion |
| `createdAt` | Date | Si | Automatico | Fecha de creacion |
| `updatedAt` | Date | Si | Automatico | Fecha de actualizacion |

## matriculas

| Campo | Tipo | Requerido | Restriccion | Descripcion |
| --- | --- | --- | --- | --- |
| `_id` | ObjectId | Si | PK | Identificador interno |
| `aprendizId` | ObjectId | Si | FK logica | Referencia a aprendiz |
| `ficha` | String | Si | Indice | Codigo de ficha |
| `jornada` | String | Si | Enum | Diurna, Nocturna, Mixta |
| `estado` | String | Si | Enum | En formacion, Condicionado, Cancelado |
| `fechaInicio` | Date | Si | Fecha valida | Inicio de formacion |
| `fechaFin` | Date | Si | Mayor o igual a inicio | Fin de formacion |
| `createdAt` | Date | Si | Automatico | Fecha de creacion |
| `updatedAt` | Date | Si | Automatico | Fecha de actualizacion |
