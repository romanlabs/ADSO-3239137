# app — panel React para administradores y auxiliares

Frontend del sistema de préstamo de portátiles: React 18 con Vite y React Router (plan D-13). Habla con la API solo por
`/api/v1` y solo desde [`src/api/client.js`](src/api/client.js).

## Cómo correrla

Con todo el sistema (lo normal):

```bash
cd ../infra
docker compose up --build --wait      # db, api y app
# abre http://localhost:5173
```

Solo la app, contra una API que ya corre en el puerto 8000:

```bash
npm install
npm run dev        # http://localhost:5173 (el origen que la API acepta en CORS_ORIGIN)
npm test           # pruebas del cliente y de la sesión (Vitest)
```

`VITE_API_URL` es el **origen** de la API visto desde el navegador (`http://localhost:8000`); el cliente le suma
`/api/v1`. Vite lo fija al compilar, por eso en el compose va como argumento de construcción.

## Cómo está organizada

```
src/
├── api/client.js          único lugar que llama a la API; convierte el cuerpo de error en ApiError(code, message, details)
├── auth/session.js        token, rol y vencimiento en sessionStorage (cerrar la pestaña cierra la sesión)
├── auth/AuthContext.jsx   iniciar y cerrar sesión; ante cualquier 401 cierra la sesión y vuelve al login
├── components/            Layout (menú por rol), RequireAuth, ErrorMessage, Pagination, LaptopPhoto, LaptopForm…
└── pages/                 una pantalla por ruta
```

| Ruta | Pantalla | Rol | Historia |
|---|---|---|---|
| `/login` | Iniciar sesión | público | HU-01 |
| `/laptops` | Inventario: búsqueda, categoría, paginación | ambos | HU-04 |
| `/laptops/new` | Nuevo portátil | admin | HU-03 |
| `/laptops/:id` | Detalle; el admin edita, sube o quita la foto y da de baja | ambos | HU-03, HU-04 |
| `/students`, `/students/:id` | Registrar, buscar y ver los préstamos de un estudiante | ambos | HU-05 |
| `/lend` | Prestar: elegir estudiante y portátil | ambos | HU-06 |
| `/loans`, `/loans/:id` | Préstamos con filtros; devolver por línea | ambos | HU-07, HU-08 |
| `/overdue` | Vencidos, más días de retraso primero | ambos | HU-09 |
| `/report` | Más solicitados por rango de fechas | admin | HU-10 |
| `/assistants` | Dar de alta auxiliares | admin | HU-02 |

**Las reglas no viven aquí.** El límite de 1 portátil, los vencidos, las unidades, el código repetido o el rango invertido
los decide la API; la app muestra su `message` en español. Ocultar un botón por rol es comodidad: quien protege es el
middleware `role:` de la API (un auxiliar que escriba `/report` ve «Sin permiso» y la API respondería 403 igual).

La foto exige token, así que no basta un `<img src>`: `LaptopPhoto` la descarga como blob y la muestra con un *object URL*.

## Recorrido de punta a punta (evidencia de T-22)

Con la base recién creada y sembrada (`cd ../tool && ./seed.sh`). Cada paso dice qué debe verse.

| # | Paso | Resultado esperado |
|---|---|---|
| 1 | Abrir `http://localhost:5173/overdue` sin sesión | Lleva a `/login` |
| 2 | Entrar como admin con una clave equivocada | «Usuario o contraseña incorrectos.» (no dice cuál falló, CA-01.2) |
| 3 | Entrar con la clave correcta | Vuelve a `/overdue`, la ruta pedida |
| 4 | Inventario | «Página 1 de 2 · 40 resultados»; buscar `thinkpad` deja 3 |
| 5 | Nuevo portátil con 2 unidades | Abre su detalle con «2 disponibles de 2» |
| 6 | Subir una foto PNG; luego cambiar el total a 5 | «Foto actualizada.»; «5 disponibles de 5» |
| 7 | Crear otro portátil con el código `tp-e14-g5` | 409: «Ya hay un portátil activo con el código de modelo «TP-E14-G5».» |
| 8 | Auxiliares: crear ` CAMILA ` con una clave de 8 o más | Aparece `camila` (minúsculas y recortado) |
| 9 | Más solicitados del mes | Filas ordenadas de mayor a menor, sin estudiantes ni auxiliares |
| 10 | Mismo reporte con «hasta» antes de «desde» | 422: «La fecha final no puede ser anterior a la inicial.» |
| 11 | Salir y entrar como `camila` | Menú sin «Más solicitados» ni «Auxiliares»; `/report` muestra «Sin permiso» |
| 12 | Estudiantes → Registrar estudiante → «Prestarle un portátil» | El estudiante queda elegido en `/lend` |
| 13 | Elegir el portátil del paso 5 y registrar | Abre el préstamo con estado «Abierto» y vencimiento a 15 días |
| 14 | Volver e intentar prestarle otro portátil | 422: «El estudiante ya tiene 1 portátil(es) sin devolver; el máximo es 1.» |
| 15 | En el préstamo, «Registrar devolución» | Estado «Cerrado» y el botón desaparece (no se puede devolver dos veces) |
| 16 | Vencidos | 3 líneas, cada una con sus días de retraso |
| 17 | Préstamos filtrados por estado «Vencidos» | 3 resultados |
| 18 | Repetir inventario, préstamos, prestar y vencidos a 375 px de ancho | Sin desplazamiento horizontal |

Este recorrido se corrió el 18-nov-2026 con Chrome automatizado (Playwright) contra el compose: los 18 pasos dieron lo
esperado y no hubo errores de JavaScript en la consola. Las únicas respuestas 4xx fueron las que el recorrido provoca a
propósito (pasos 2, 7, 10 y 14).
