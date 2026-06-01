"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.env = void 0;
exports.env = {
    nodeEnv: process.env.NODE_ENV || 'development',
    port: Number(process.env.PORT || 3001),
    mongoUri: process.env.MONGO_URI || 'mongodb://localhost:27017/sena_matricula',
    corsOrigin: process.env.CORS_ORIGIN || '*',
};
//# sourceMappingURL=env.js.map