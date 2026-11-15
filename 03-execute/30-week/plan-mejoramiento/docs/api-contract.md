# Contrato de la API — Préstamo de portátiles

El contrato HTTP es **independiente del lenguaje**: lo implementa el backend Laravel (`api/routes/api.php`) y lo consume el frontend
React (`app/src/api/client.js`) y el sembrador (`tool/`). Qué hace cada operación está en [`spec.md`](spec.md); qué se guarda, en
[`data-model.md`](data-model.md).

## 1. Convenciones

| Tema | Regla |
|---|---|
| Base | `/api/v1` |
| Formato | JSON, UTF-8. Nombres de campo en `snake_case` |
| Identificadores | UUID v4 como texto |
| Fechas | ISO 8601 en UTC, con `Z` (`2026-11-16T14:15:00Z`) |
| Dinero | No hay. El sistema no maneja importes |
| Autenticación | `Authorization: Bearer <token>` en todo salvo `POST /auth/login` y `GET /health` |
| Paginación | `?page=1&size=20`. `size` máximo 50. La respuesta es `{ "items": [...], "page": 1, "size": 20, "total": 134 }` |
| Idioma | Mensajes de error en español; campos y códigos en inglés |

### Cuerpo de error (único para todos los errores)

```json
{
  "code": "LAPTOP_NOT_AVAILABLE",
  "message": "No quedan unidades disponibles de «ThinkPad E14 Gen 5».",
  "details": [ { "field": "items[0].laptop_id", "issue": "sin unidades" } ]
}
```

`details` puede ir vacío. **Nunca** incluye trazas, SQL, rutas del servidor ni la contraseña. En Laravel lo produce un único renderizador
de excepciones registrado en `bootstrap/app.php`; las respuestas por defecto del framework (HTML, `message` + `errors`) no llegan al cliente.

### Códigos de estado

| Estado | Cuándo |
|---|---|
| 200 / 201 / 204 | Éxito / creado / sin contenido |
| 401 | Sin token, token inválido o vencido, credenciales inválidas |
| 403 | Rol insuficiente |
| 404 | No existe (o está dado de baja) |
| 409 | Conflicto de estado: duplicado, sin unidades, ya devuelto, estudiante con vencidos, portátil con préstamos abiertos |
| 422 | Validación: campo inválido, regla de entrada rota, rango de fechas invertido |

## 2. Códigos de error de negocio

| `code` | Estado | Origen |
|---|---|---|
| `INVALID_CREDENTIALS` | 401 | CA-01.2; también sin token o token vencido (CA-01.3) |
| `FORBIDDEN_ROLE` | 403 | CA-01.3 |
| `NOT_FOUND` | 404 | Cualquier recurso |
| `USERNAME_TAKEN` | 409 | CA-02.2 |
| `MODEL_CODE_TAKEN` | 409 | CA-03.2 |
| `DOCUMENT_TAKEN` | 409 | CA-05.1 |
| `LAPTOP_HAS_OPEN_LOANS` | 409 | CA-03.5 |
| `LAPTOP_NOT_AVAILABLE` | 409 | CA-06.3, RNF-2 |
| `STUDENT_HAS_OVERDUE` | 409 | CA-06.5 |
| `ITEM_ALREADY_RETURNED` | 409 | CA-07.2 |
| `STUDENT_LIMIT_EXCEEDED` | 422 | CA-06.4 |
| `DUPLICATE_LAPTOP_IN_LOAN` | 422 | CA-06.2 |
| `VALIDATION_ERROR` | 422 | Cualquier campo inválido |
| `INVALID_DATE_RANGE` | 422 | CA-10.2 |

## 3. Endpoints

Son **21**. Todas las rutas van bajo `/api/v1`.

### 3.1 Autenticación y salud

| Método | Ruta | Rol | Descripción |
|---|---|---|---|
| POST | `/auth/login` | público | Devuelve token |
| GET | `/health` | público | `{ "status": "ok" }` |

`POST /auth/login`

```json
// petición
{ "username": "camila", "password": "********" }
// 200
{ "access_token": "eyJ...", "token_type": "Bearer", "expires_in": 28800, "role": "assistant" }
```

### 3.2 Usuarios

| Método | Ruta | Rol | Descripción |
|---|---|---|---|
| POST | `/users` | admin | Crea un auxiliar |

```json
// petición  (role solo admite "assistant")
{ "username": "Camila ", "password": "********", "role": "assistant" }
// 201  (sin password ni password_hash)
{ "id": "…", "username": "camila", "role": "assistant" }
```

La clave debe tener al menos 8 caracteres (422 `VALIDATION_ERROR`).

### 3.3 Categorías

| Método | Ruta | Rol | Descripción |
|---|---|---|---|
| GET | `/categories` | autenticado | Las cinco categorías, orden por nombre |

### 3.4 Portátiles

| Método | Ruta | Rol | Descripción |
|---|---|---|---|
| GET | `/laptops` | autenticado | Búsqueda: `q`, `category_id`, `page`, `size` |
| GET | `/laptops/{id}` | autenticado | Detalle |
| POST | `/laptops` | admin | Crea |
| PUT | `/laptops/{id}` | admin | Reemplaza nombre, marca, categoría y total |
| DELETE | `/laptops/{id}` | admin | Baja lógica → 204 |
| PUT | `/laptops/{id}/photo` | admin | Sube foto (`multipart/form-data`, campo `file`) |
| DELETE | `/laptops/{id}/photo` | admin | Quita la foto → 204 |
| GET | `/laptops/{id}/photo` | autenticado | Devuelve el binario (`image/jpeg` o `image/png`) o 404 |

Portátil (respuesta):

```json
{
  "id": "…", "name": "ThinkPad E14 Gen 5", "brand": "Lenovo", "model_code": "TP-E14-G5",
  "category": { "id": "…", "name": "Programación" },
  "total_units": 4, "available_units": 3, "photo_key": null
}
```

`POST /laptops` (petición): `name`, `brand`, `model_code`, `category_id`, `total_units`. Las disponibles las calcula el servidor.
`PUT /laptops/{id}`: `name`, `brand`, `category_id`, `total_units` (el código de modelo no se edita).
`photo_key` es una clave opaca: para pintar la foto se usa `GET /laptops/{id}/photo`, que devuelve el binario o 404.
`PUT /laptops/{id}/photo` → 200 con el portátil actualizado. Archivo de más de 2 MB o que no sea JPG/PNG por su contenido: 422.

### 3.5 Estudiantes

| Método | Ruta | Rol | Descripción |
|---|---|---|---|
| GET | `/students` | admin, assistant | Lista y busca: `q`, `page`, `size` |
| GET | `/students/{id}` | admin, assistant | Detalle con `active_loans` (conteo sin devolver) |
| POST | `/students` | admin, assistant | Crea: `full_name`, `document_number` |

```json
// GET /students/{id}
{ "id": "…", "full_name": "Laura Gómez", "document_number": "1001234567", "active_loans": 1 }
```

### 3.6 Préstamos

| Método | Ruta | Rol | Descripción |
|---|---|---|---|
| POST | `/loans` | admin, assistant | Registra un préstamo |
| GET | `/loans` | admin, assistant | Lista: `from`, `to`, `status`, `student_id`, `page`, `size` |
| GET | `/loans/{id}` | admin, assistant | Detalle con líneas y estado |
| POST | `/loans/{loan_id}/items/{item_id}/return` | admin, assistant | Devuelve una línea |
| GET | `/loans/overdue` | admin, assistant | Líneas vencidas |

`POST /loans`

```json
// petición  (al menos 1 elemento, sin portátiles repetidos; el límite por estudiante es 1 sin devolver)
{ "student_id": "…", "items": [ { "laptop_id": "…" } ] }
// 201
{
  "id": "…", "student_id": "…", "loaned_at": "2026-11-16T14:15:00Z", "status": "open",
  "items": [
    { "id": "…", "laptop_id": "…", "laptop_name": "ThinkPad E14 Gen 5", "model_code": "TP-E14-G5",
      "category_name": "Programación", "due_at": "2026-12-01T14:15:00Z", "returned_at": null }
  ]
}
```

Orden de validación de `POST /loans`: (1) forma de la petición → `VALIDATION_ERROR`; (2) portátil repetido →
`DUPLICATE_LAPTOP_IN_LOAN`; (3) estudiante o portátil inexistente → `NOT_FOUND`; (4) estudiante con vencidos →
`STUDENT_HAS_OVERDUE`; (5) sin devolver + líneas pedidas > 1 → `STUDENT_LIMIT_EXCEEDED` con `details` = conteo actual;
(6) sin unidades → `LAPTOP_NOT_AVAILABLE`.

`status` es calculado: `open` (hay líneas sin devolver y ninguna vencida), `overdue` (alguna sin devolver y vencida), `closed` (todas devueltas).

`POST /loans/{loan_id}/items/{item_id}/return` → 200 con la línea (`returned_at` lleno).

`GET /loans/overdue` → `items`: `{ "loan_id", "item_id", "laptop_name", "student_name", "due_at", "days_late" }`. Aquí sí se muestra el
nombre del estudiante, porque es un listado operativo; **el reporte del §3.7 no**.

### 3.7 Reporte

| Método | Ruta | Rol | Descripción |
|---|---|---|---|
| GET | `/reports/most-requested` | admin | `from` y `to` obligatorios (fecha `YYYY-MM-DD`, ambos inclusive) |

```json
{
  "from": "2026-11-01", "to": "2026-11-30",
  "rows": [
    { "laptop_id": "…", "laptop_name": "ThinkPad E14 Gen 5", "category_name": "Programación", "times_requested": 12 }
  ]
}
```

Una fila por `laptop_id + laptop_name + category_name` congelados. Orden: `times_requested` descendente, luego nombre. Sin estudiantes
ni auxiliares.

## 4. Seguridad

| Aspecto | Regla |
|---|---|
| Token | JWT HS256 firmado con `JWT_SECRET`, 8 horas, con `sub` (id del usuario) y `role`. Sin datos personales dentro |
| Contraseñas | Solo se guarda el hash Argon2id; el hash nunca sale en ninguna respuesta |
| Autorización | Se evalúa en cada endpoint por rol (middleware `role:`); un 403 no revela si el recurso existe |
| CORS | Solo el origen del frontend configurado en `CORS_ORIGIN` |
| Subida de foto | Solo `image/jpeg` e `image/png`, máx. 2 MB; el tipo se valida por contenido (`finfo`), no por extensión |

## 5. Equivalencias con el sistema de referencia

| Referencia (biblioteca) | Este sistema |
|---|---|
| `/books`, `book`, ISBN, ejemplares | `/laptops`, `laptop`, `model_code`, unidades |
| `/books/{id}/cover`, `cover_key` | `/laptops/{id}/photo`, `photo_key` |
| `/members`, socio | `/students`, estudiante |
| `librarian` | `assistant` (auxiliar) |
| `/reports/most-borrowed`, `times_borrowed` | `/reports/most-requested`, `times_requested` |
| Máx. 3 libros, 14 días | Máx. 1 portátil, 15 días |
