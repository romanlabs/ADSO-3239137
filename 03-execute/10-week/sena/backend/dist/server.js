"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const app_1 = __importDefault(require("./app"));
const database_1 = require("./config/database");
const env_1 = require("./config/env");
(0, database_1.connectDatabase)()
    .then(() => {
    app_1.default.listen(env_1.env.port, () => {
        console.log(`Servidor corriendo en puerto ${env_1.env.port}`);
    });
})
    .catch((error) => {
    console.error('Error al conectar a MongoDB:', error);
    process.exit(1);
});
//# sourceMappingURL=server.js.map