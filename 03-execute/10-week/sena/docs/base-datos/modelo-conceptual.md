# Modelo Conceptual

El sistema administra dos conceptos principales:

- Aprendiz: persona registrada en el sistema academico.
- Matricula: registro que vincula un aprendiz con una ficha, jornada, estado y periodo de formacion.

Relacion:

- Un aprendiz puede tener una o muchas matriculas.
- Cada matricula pertenece a un unico aprendiz.

```mermaid
erDiagram
  APRENDIZ ||--o{ MATRICULA : tiene
  APRENDIZ {
    string documento
    string nombre
    string email
    string telefono
    string programa
  }
  MATRICULA {
    objectId aprendizId
    string ficha
    string jornada
    string estado
    date fechaInicio
    date fechaFin
  }
```
