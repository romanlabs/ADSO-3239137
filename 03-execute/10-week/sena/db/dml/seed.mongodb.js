use('sena_matricula');

db.aprendices.deleteMany({});
db.matriculas.deleteMany({});

const aprendices = db.aprendices.insertMany([
  {
    documento: '123456789',
    nombre: 'Roman Bolanos',
    email: 'roman@test.com',
    telefono: '3001234567',
    programa: 'Tecnologo en Desarrollo de Software',
    createdAt: new Date(),
    updatedAt: new Date(),
  },
  {
    documento: '987654321',
    nombre: 'Laura Gomez',
    email: 'laura@test.com',
    telefono: '3015558844',
    programa: 'Analisis y Desarrollo de Software',
    createdAt: new Date(),
    updatedAt: new Date(),
  },
]);

db.matriculas.insertMany([
  {
    aprendizId: aprendices.insertedIds[0],
    ficha: '2876543',
    jornada: 'Diurna',
    estado: 'Condicionado',
    fechaInicio: ISODate('2026-01-15'),
    fechaFin: ISODate('2027-12-15'),
    createdAt: new Date(),
    updatedAt: new Date(),
  },
  {
    aprendizId: aprendices.insertedIds[1],
    ficha: '2876544',
    jornada: 'Mixta',
    estado: 'En formacion',
    fechaInicio: ISODate('2026-02-01'),
    fechaFin: ISODate('2027-11-30'),
    createdAt: new Date(),
    updatedAt: new Date(),
  },
]);
