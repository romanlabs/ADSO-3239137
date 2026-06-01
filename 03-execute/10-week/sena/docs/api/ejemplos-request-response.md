# Ejemplos Request Response

## Crear aprendiz

```json
{
  "documento": "123456789",
  "nombre": "Roman Bolanos",
  "email": "roman@test.com",
  "telefono": "3001234567",
  "programa": "Tecnologo en Desarrollo de Software"
}
```

## Crear matricula

```json
{
  "aprendizId": "6a1dcd54f93909b9cb25ae42",
  "ficha": "2876543",
  "jornada": "Diurna",
  "estado": "Condicionado",
  "fechaInicio": "2026-01-15",
  "fechaFin": "2027-12-15"
}
```

## Respuesta exitosa

```json
{
  "success": true,
  "message": "Matricula creada correctamente",
  "data": {
    "_id": "6a1dcd54f93909b9cb25ae43",
    "ficha": "2876543"
  }
}
```
