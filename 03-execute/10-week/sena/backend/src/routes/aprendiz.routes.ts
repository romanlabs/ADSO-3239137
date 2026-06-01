import { Router } from 'express';
import { AprendizController } from '../controllers/aprendiz.controller';
import { asyncHandler } from '../utils/async-handler';
import { validateObjectId } from '../middlewares/validate-object-id.middleware';

const router = Router();
const controller = new AprendizController();

router.get('/', asyncHandler(controller.getAll));
router.get('/:id', validateObjectId(), asyncHandler(controller.getById));
router.post('/', asyncHandler(controller.create));
router.put('/:id', validateObjectId(), asyncHandler(controller.update));
router.delete('/:id', validateObjectId(), asyncHandler(controller.delete));

export default router;
