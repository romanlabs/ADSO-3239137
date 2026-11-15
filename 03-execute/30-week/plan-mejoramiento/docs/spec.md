# Especificación — Préstamo de portátiles a estudiantes

**Qué se construye y por qué.** Un sistema para que una institución educativa lleve el inventario de portátiles que presta, sus
estudiantes y los préstamos que registran sus auxiliares, y vea qué portátiles se solicitan más. Hoy el préstamo se anota en una
planilla: no se sabe cuántas unidades quedan, quién tiene un equipo vencido ni qué modelos conviene comprar.

Rige bajo [`constitution.md`](constitution.md). El modelo de datos vive solo en [`data-model.md`](data-model.md) y el contrato HTTP solo
en [`api-contract.md`](api-contract.md). Identificador del dominio: `laptops`.

---

## 1. Actores

| Actor | Qué hace | Cómo se autentica |
|---|---|---|
| **Administrador** (`admin`) | Mantiene el inventario de portátiles y los estudiantes; da de alta auxiliares; ve todos los reportes | Usuario y contraseña → token |
| **Auxiliar** (`assistant`) | Registra préstamos y devoluciones; consulta inventario, estudiantes y vencidos | Usuario y contraseña → token |
| **Estudiante** | Persona que recibe un portátil prestado. **No entra al sistema**: es un dato, no un usuario | — |

El rol `admin` lo provisiona el despliegue desde variables de entorno. **Nadie lo otorga en ejecución.**

## 2. Alcance

**Dentro:** inventario de portátiles con foto opcional, cinco categorías fijas (básicos, diseño, programación, tabletas, accesorios),
estudiantes, préstamos con su línea, devolución por línea, portátiles vencidos, reporte de más solicitados, inicio de sesión con roles.

**Fuera, y no se reabre:** multas y cobros, reservas, renovaciones, correo o SMS, importación masiva, préstamos entre sedes, número de
serie por unidad, mantenimiento o daños, atributos del portátil más allá de los de §3 (sin procesador, sin memoria, sin año).

## 3. Historias de usuario y criterios de aceptación

### HU-01 · Iniciar sesión
Como usuario quiero autenticarme para operar según mi rol.
- **CA-01.1** Con credenciales válidas se recibe un token con vigencia de 8 horas.
- **CA-01.2** Con credenciales inválidas responde 401 con un mensaje que **no** dice si falló el usuario o la clave.
- **CA-01.3** Todo endpoint salvo el inicio de sesión y `/health` exige token. Sin token: 401. Rol insuficiente: 403.

### HU-02 · Dar de alta auxiliares (admin)
- **CA-02.1** El admin crea un usuario con nombre, clave y rol `assistant`.
- **CA-02.2** El nombre de usuario se guarda en minúsculas y recortado; es único. Repetido: 409.
- **CA-02.3** Pedir el rol `admin` por la API responde 422. La clave nunca se devuelve ni se registra en logs.

### HU-03 · Gestionar portátiles (admin)
- **CA-03.1** Crear un portátil con nombre, marca, código de modelo, categoría y total de unidades. Las disponibles arrancan iguales al total.
- **CA-03.2** El código de modelo tiene de 4 a 20 caracteres (letras, dígitos y guion), se guarda en mayúsculas y es único entre portátiles
  activos. Repetido: 409.
- **CA-03.3** Total de unidades ≥ 1. Cambiar el total ajusta las disponibles en la misma diferencia y **nunca** deja disponibles < 0 (422).
- **CA-03.4** Subir foto (JPG/PNG, máx. 2 MB) guarda una **clave opaca**; sin foto el campo es `null`, nunca cadena vacía.
- **CA-03.5** Eliminar un portátil es **baja lógica**: deja de aparecer en el inventario pero sigue en los préstamos pasados. Un portátil
  con préstamos abiertos no se da de baja (409).

### HU-04 · Buscar portátiles
- **CA-04.1** Búsqueda por texto parcial en nombre o marca, filtro por categoría, solo portátiles activos, orden por nombre, paginada
  (`page`, `size`, máx. 50).
- **CA-04.2** La respuesta incluye el total de resultados.
- **CA-04.3** Consultar un portátil por id inexistente o dado de baja: 404.

### HU-05 · Gestionar estudiantes
- **CA-05.1** El admin y el auxiliar crean estudiantes con nombre completo y documento. El documento es único: repetido 409.
- **CA-05.2** Se lista y se busca por nombre o documento, paginado.
- **CA-05.3** Los estudiantes no se eliminan.

### HU-06 · Registrar un préstamo
- **CA-06.1** Un préstamo es de **un** estudiante, lo registra el usuario autenticado y trae al menos una línea. Como el límite es de
  **1** portátil sin devolver (RN-2), en la práctica un préstamo tiene una sola línea.
- **CA-06.2** No se repite el mismo portátil en un préstamo (422).
- **CA-06.3** Cada línea descuenta **1** unidad disponible en la misma operación. Sin unidades: 409 y **no se registra nada**.
- **CA-06.4** Un estudiante no puede tener más de **1 portátil sin devolver** en total (422 con el conteo actual).
- **CA-06.5** Un estudiante con algún portátil **vencido** no puede pedir otro (409).
- **CA-06.6** La fecha de vencimiento es `loaned_at + 15 días`. La línea congela nombre del portátil, código de modelo y nombre de
  categoría **en ese instante**.
- **CA-06.7** El préstamo registrado no se edita ni se borra.

### HU-07 · Registrar una devolución
- **CA-07.1** Se devuelve **una línea** a la vez. Pone `returned_at` y suma 1 a las disponibles.
- **CA-07.2** Devolver una línea ya devuelta: 409. **Nunca** suma dos veces.
- **CA-07.3** Las disponibles jamás superan el total (la base lo garantiza).
- **CA-07.4** Cuando todas las líneas están devueltas, el préstamo figura como **cerrado**.

### HU-08 · Ver un préstamo y listar préstamos
- **CA-08.1** Consultar un préstamo trae sus líneas y su estado calculado (`open`, `overdue`, `closed`).
- **CA-08.2** Listar por rango de fechas, estado y estudiante, orden por fecha descendente, paginado.
- **CA-08.3** El estado **se calcula**, no se almacena.

### HU-09 · Portátiles vencidos
- **CA-09.1** Lista las líneas sin devolver con `due_at` anterior a ahora: portátil, estudiante, días de retraso.
- **CA-09.2** Ordenadas por más días de retraso primero. Solo `admin` y `assistant`.

### HU-10 · Reporte de portátiles más solicitados
- **CA-10.1** Dado un rango de fechas, una fila por **portátil y nombre de categoría congelado**, con la cantidad de veces solicitado,
  ordenado de mayor a menor.
- **CA-10.2** Fin anterior al inicio: 422. Rango sin préstamos: lista vacía, no error.
- **CA-10.3** El reporte **no** se desglosa por auxiliar ni por estudiante.
- **CA-10.4** Un reporte de un periodo cerrado **no cambia** aunque luego se renombre el portátil o su categoría.
- **CA-10.5** Solo `admin`.

## 4. Reglas de negocio

| # | Regla |
|---|---|
| RN-1 | Unidades disponibles entre 0 y total, siempre. |
| RN-2 | Plazo de préstamo: 15 días. Máximo 1 portátil sin devolver por estudiante. |
| RN-3 | Un estudiante con vencidos no recibe más hasta devolverlos. |
| RN-4 | Lo congelado en la línea (nombre, código de modelo, categoría) no sigue al inventario. |
| RN-5 | Préstamos y devoluciones no se borran. Los portátiles solo tienen baja lógica. |
| RN-6 | Las fechas son instantes UTC. Mensajes al usuario en español; código y columnas en inglés. |

## 5. Requisitos no funcionales

| # | Requisito | Cómo se mide |
|---|---|---|
| RNF-1 | La búsqueda de portátiles responde en menos de 300 ms con 5 000 portátiles | Prueba de carga con el sembrador de `tool` (`--laptops 5000`) |
| RNF-2 | Dos operadores entregando la **última unidad** a la vez: uno gana, el otro recibe 409 | Prueba concurrente (`tests/Feature/LastUnitConcurrencyTest.php`) |
| RNF-3 | La contraseña nunca viaja de vuelta ni se guarda en claro; nunca aparece en logs | Revisión de logs y de respuestas |
| RNF-4 | Todo error HTTP usa el mismo cuerpo (ver contrato) | Prueba de contrato |
| RNF-5 | El sistema levanta con un solo comando y la base arranca vacía | `README` de `infra` |

## 6. Trazabilidad

| Historia | Reglas | Endpoints ([`api-contract.md`](api-contract.md)) | Tareas ([`tasks.md`](tasks.md)) |
|---|---|---|---|
| HU-01 | RNF-3 | `POST /auth/login` | T-07, T-14, T-16 |
| HU-02 | RNF-3 | `POST /users` | T-06, T-07, T-14 |
| HU-03 | RN-1, RN-5 | `POST/PUT/DELETE /laptops`, `PUT/DELETE/GET /laptops/{id}/photo` | T-04, T-08, T-12, T-15 |
| HU-04 | RNF-1 | `GET /laptops`, `GET /laptops/{id}`, `GET /categories` | T-08, T-12 |
| HU-05 | — | `GET/POST /students`, `GET /students/{id}` | T-06, T-12 |
| HU-06 | RN-1, RN-2, RN-3, RN-4, RNF-2 | `POST /loans` | T-05, T-09, T-13 |
| HU-07 | RN-1, RN-5 | `POST /loans/{loan_id}/items/{item_id}/return` | T-10, T-13 |
| HU-08 | RN-5 | `GET /loans`, `GET /loans/{id}` | T-05, T-13 |
| HU-09 | RN-3, RN-6 | `GET /loans/overdue` | T-10 |
| HU-10 | RN-4 | `GET /reports/most-requested` | T-11 |
