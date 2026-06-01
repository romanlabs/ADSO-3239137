"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.requestLogger = requestLogger;
function requestLogger(req, res, next) {
    const startedAt = Date.now();
    res.on('finish', () => {
        const elapsed = Date.now() - startedAt;
        console.log(`${req.method} ${req.originalUrl} ${res.statusCode} ${elapsed}ms`);
    });
    next();
}
//# sourceMappingURL=request-logger.middleware.js.map