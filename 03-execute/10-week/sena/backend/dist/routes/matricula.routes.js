"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const matricula_controller_1 = require("../controllers/matricula.controller");
const validate_object_id_middleware_1 = require("../middlewares/validate-object-id.middleware");
const async_handler_1 = require("../utils/async-handler");
const router = (0, express_1.Router)();
const controller = new matricula_controller_1.MatriculaController();
router.get('/', (0, async_handler_1.asyncHandler)(controller.getAll));
router.get('/aprendiz/:aprendizId', (0, validate_object_id_middleware_1.validateObjectId)('aprendizId'), (0, async_handler_1.asyncHandler)(controller.getByAprendiz));
router.get('/:id', (0, validate_object_id_middleware_1.validateObjectId)(), (0, async_handler_1.asyncHandler)(controller.getById));
router.post('/', (0, async_handler_1.asyncHandler)(controller.create));
router.put('/:id', (0, validate_object_id_middleware_1.validateObjectId)(), (0, async_handler_1.asyncHandler)(controller.update));
router.delete('/:id', (0, validate_object_id_middleware_1.validateObjectId)(), (0, async_handler_1.asyncHandler)(controller.delete));
exports.default = router;
//# sourceMappingURL=matricula.routes.js.map