use('sena_matricula');

db.runCommand({
  collMod: 'aprendices',
  validator: {
    $jsonSchema: {
      bsonType: 'object',
      required: ['documento', 'nombre', 'email', 'telefono', 'programa'],
      properties: {
        documento: { bsonType: 'string' },
        nombre: { bsonType: 'string' },
        email: { bsonType: 'string' },
        telefono: { bsonType: 'string' },
        programa: { bsonType: 'string' },
      },
    },
  },
  validationLevel: 'strict',
});

db.runCommand({
  collMod: 'matriculas',
  validator: {
    $jsonSchema: {
      bsonType: 'object',
      required: ['aprendizId', 'ficha', 'jornada', 'estado', 'fechaInicio', 'fechaFin'],
      properties: {
        aprendizId: { bsonType: 'objectId' },
        ficha: { bsonType: 'string' },
        jornada: { enum: ['Diurna', 'Nocturna', 'Mixta'] },
        estado: { enum: ['En formacion', 'Condicionado', 'Cancelado'] },
        fechaInicio: { bsonType: 'date' },
        fechaFin: { bsonType: 'date' },
      },
    },
  },
  validationLevel: 'strict',
});
