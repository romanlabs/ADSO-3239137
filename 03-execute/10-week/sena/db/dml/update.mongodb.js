use('sena_matricula');

db.aprendices.updateOne(
  { documento: '123456789' },
  { $set: { telefono: '3007654321', updatedAt: new Date() } },
);

db.matriculas.updateOne(
  { ficha: '2876543' },
  { $set: { estado: 'En formacion', updatedAt: new Date() } },
);
