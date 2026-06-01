use('sena_matricula');

db.aprendices.find({ programa: /Desarrollo/i }).sort({ createdAt: -1 });

db.matriculas.aggregate([
  {
    $lookup: {
      from: 'aprendices',
      localField: 'aprendizId',
      foreignField: '_id',
      as: 'aprendiz',
    },
  },
  { $unwind: '$aprendiz' },
  {
    $project: {
      ficha: 1,
      jornada: 1,
      estado: 1,
      fechaInicio: 1,
      fechaFin: 1,
      'aprendiz.documento': 1,
      'aprendiz.nombre': 1,
      'aprendiz.programa': 1,
    },
  },
]);
