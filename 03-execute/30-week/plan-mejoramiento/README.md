Este proyecto es la solución del plan de mejoramiento del aprendiz A06, ficha 3239137, dominio laptops.

# Préstamo de portátiles a estudiantes

Sistema para que una institución lleve su inventario de portátiles, sus estudiantes y los préstamos
que hacen sus auxiliares, y vea qué portátiles se solicitan más.

| Carpeta | Contenido |
|---|---|
| [`docs/`](docs/) | Especificación: constitución, spec, plan, arquitectura, modelo de datos, contrato de la API, tareas y ADR |
| [`api/`](api/) | Backend en PHP con Laravel (arquitectura hexagonal) |
| [`app/`](app/) | Frontend en React para administradores y auxiliares |
| [`page/`](page/) | Sitio público estático que presenta el producto |
| [`infra/`](infra/) | Contenedores: PostgreSQL 16 vacío, API y app con un solo comando |
| [`tool/`](tool/) | Sembrador de datos de demostración que usa la API |

El acta del plan, con código y sin datos personales, está en [`Plan-Mejoramiento-A06.pdf`](Plan-Mejoramiento-A06.pdf).

> Estado: en construcción. Este README se completa con las instrucciones de ejecución a medida que avanza el proyecto.
