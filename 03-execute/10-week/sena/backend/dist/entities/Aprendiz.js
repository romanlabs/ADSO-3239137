"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.AprendizModel = void 0;
const mongoose_1 = require("mongoose");
const AprendizSchema = new mongoose_1.Schema({
    documento: { type: String, required: true, unique: true, trim: true },
    nombre: { type: String, required: true, trim: true },
    email: { type: String, required: true, lowercase: true, trim: true },
    telefono: { type: String, required: true, trim: true },
    programa: { type: String, required: true, trim: true },
}, { timestamps: true });
AprendizSchema.index({ nombre: 1 });
AprendizSchema.index({ programa: 1 });
exports.AprendizModel = (0, mongoose_1.model)('Aprendiz', AprendizSchema);
//# sourceMappingURL=Aprendiz.js.map