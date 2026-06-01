"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const aprendiz_controller_1 = require("../controllers/aprendiz.controller");
const async_handler_1 = require("../utils/async-handler");
const validate_object_id_middleware_1 = require("../middlewares/validate-object-id.middleware");
const router = (0, express_1.Router)();
const controller = new aprendiz_controller_1.AprendizController();
router.get('/', (0, async_handler_1.asyncHandler)(controller.getAll));
router.get('/:id', (0, validate_object_id_middleware_1.validateObjectId)(), (0, async_handler_1.asyncHandler)(controller.getById));
router.post('/', (0, async_handler_1.asyncHandler)(controller.create));
router.put('/:id', (0, validate_object_id_middleware_1.validateObjectId)(), (0, async_handler_1.asyncHandler)(controller.update));
router.delete('/:id', (0, validate_object_id_middleware_1.validateObjectId)(), (0, async_handler_1.asyncHandler)(controller.delete));
exports.default = router;
//# sourceMappingURL=aprendiz.routes.js.map