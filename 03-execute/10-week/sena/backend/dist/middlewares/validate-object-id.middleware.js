"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.validateObjectId = validateObjectId;
const mongoose_1 = require("mongoose");
function validateObjectId(paramName = 'id') {
    return (req, res, next) => {
        const value = String(req.params[paramName] || '');
        if (!mongoose_1.Types.ObjectId.isValid(value)) {
            res.status(400).json({
                success: false,
                error: `Parametro ${paramName} invalido`,
            });
            return;
        }
        next();
    };
}
//# sourceMappingURL=validate-object-id.middleware.js.map