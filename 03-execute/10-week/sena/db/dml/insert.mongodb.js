use('sena_matricula');

const aprendiz = db.aprendices.insertOne({
  documento: '123456789',
  nombre: 'Roman Bolanos',
  email: 'roman@test.com',
  telefono: '3001234567',
  programa: 'Tecnologo en Desarrollo de Software',
  createdAt: new Date(),
  updatedAt: new Date(),
});

db.matriculas.insertOne({
  aprendizId: aprendiz.insertedId,
  ficha: '2876543',
  jornada: 'Diurna',
  estado: 'Condicionado',
  fechaInicio: ISODate('2026-01-15'),
  fechaFin: ISODate('2027-12-15'),
  createdAt: new Date(),
  updatedAt: new Date(),
});
