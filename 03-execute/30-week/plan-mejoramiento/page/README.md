# page — Sitio público

Página estática de presentación del sistema de préstamo de portátiles. Solo HTML y CSS: no llama a la API ni necesita
servidor.

## Cómo abrirlo

Abrir `index.html` en el navegador. También se puede servir con cualquier servidor estático, por ejemplo:

```bash
python -m http.server 8080
```

## Qué presenta

Inventario, categorías, estudiantes, préstamos, devoluciones, vencidos, más solicitados, reglas del préstamo y roles.

## Adaptable

`styles.css` tiene dos cortes con `@media`: hasta 900 px (una columna en la portada) y hasta 600 px (tarjetas en una
sola columna). Se revisa en 375 px (móvil) y 1366 px (escritorio).

## Cómo se comprobó (evidencia de T-23)

| Prueba | Resultado |
|---|---|
| Abrir `index.html` con doble clic, sin API ni servidor | Carga completo: no hace peticiones de red ni usa JavaScript |
| 375 px de ancho | Portada en una columna, tarjetas una debajo de otra, sin desplazamiento horizontal |
| 1366 px de ancho | Portada en dos columnas y funciones en una cuadrícula de tres |
| Menú `Funciones`, `Reglas`, `Roles` | Cada enlace baja a su sección |
