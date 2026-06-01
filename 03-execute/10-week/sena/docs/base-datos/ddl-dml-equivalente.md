# DDL Y DML Equivalente En MongoDB

MongoDB no usa SQL DDL/DML tradicional, pero las operaciones equivalentes son:

## DDL

- Crear colecciones: `db.createCollection`.
- Crear indices: `db.collection.createIndex`.
- Definir restricciones: `validator` con `$jsonSchema`.

Archivos:

- `db/ddl/create-collections.mongodb.js`
- `db/ddl/indexes.mongodb.js`
- `db/ddl/validators.mongodb.js`

## DML

- INSERT: `insertOne`, `insertMany`.
- SELECT: `find`, `aggregate`.
- UPDATE: `updateOne`.
- DELETE: `deleteOne`.

Archivos:

- `db/dml/insert.mongodb.js`
- `db/dml/select.mongodb.js`
- `db/dml/update.mongodb.js`
- `db/dml/delete.mongodb.js`
- `db/dml/seed.mongodb.js`
