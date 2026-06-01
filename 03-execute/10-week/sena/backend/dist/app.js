"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = __importDefault(require("express"));
const cors_1 = __importDefault(require("cors"));
const aprendiz_routes_1 = __importDefault(require("./routes/aprendiz.routes"));
const matricula_routes_1 = __importDefault(require("./routes/matricula.routes"));
const env_1 = require("./config/env");
const request_logger_middleware_1 = require("./middlewares/request-logger.middleware");
const not_found_middleware_1 = require("./middlewares/not-found.middleware");
const error_middleware_1 = require("./middlewares/error.middleware");
const app = (0, express_1.default)();
app.use((0, cors_1.default)({ origin: env_1.env.corsOrigin }));
app.use(express_1.default.json());
app.use(request_logger_middleware_1.requestLogger);
app.get('/health', (_req, res) => {
    res.status(200).json({ status: 'ok', timestamp: new Date().toISOString() });
});
app.get('/', (_req, res) => {
    res.json({ message: 'SENA Matricula API - MVP' });
});
app.use('/api/aprendices', aprendiz_routes_1.default);
app.use('/api/matriculas', matricula_routes_1.default);
app.use(not_found_middleware_1.notFoundHandler);
app.use(error_middleware_1.errorHandler);
exports.default = app;
//# sourceMappingURL=app.js.map