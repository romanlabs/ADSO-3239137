use('sena_matricula');

db.aprendices.createIndex({ documento: 1 }, { unique: true });
db.aprendices.createIndex({ nombre: 1 });
db.aprendices.createIndex({ programa: 1 });

db.matriculas.createIndex({ aprendizId: 1 });
db.matriculas.createIndex({ ficha: 1 });
db.matriculas.createIndex({ estado: 1 });
db.matriculas.createIndex({ aprendizId: 1, ficha: 1 });
