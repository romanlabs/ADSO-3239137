"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.MatriculaModel = void 0;
const mongoose_1 = require("mongoose");
const MatriculaSchema = new mongoose_1.Schema({
    aprendizId: { type: mongoose_1.Schema.Types.ObjectId, ref: 'Aprendiz', required: true, index: true },
    ficha: { type: String, required: true, trim: true },
    jornada: { type: String, enum: ['Diurna', 'Nocturna', 'Mixta'], required: true },
    estado: {
        type: String,
        enum: ['En formacion', 'Condicionado', 'Cancelado'],
        default: 'En formacion',
    },
    fechaInicio: { type: Date, required: true },
    fechaFin: { type: Date, required: true },
}, { timestamps: true });
MatriculaSchema.index({ ficha: 1 });
MatriculaSchema.index({ estado: 1 });
MatriculaSchema.index({ aprendizId: 1, ficha: 1 });
exports.MatriculaModel = (0, mongoose_1.model)('Matricula', MatriculaSchema);
//# sourceMappingURL=Matricula.js.map