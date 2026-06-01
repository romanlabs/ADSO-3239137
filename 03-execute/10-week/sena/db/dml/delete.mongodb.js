use('sena_matricula');

db.matriculas.deleteOne({ ficha: '2876543' });
db.aprendices.deleteOne({ documento: '123456789' });
