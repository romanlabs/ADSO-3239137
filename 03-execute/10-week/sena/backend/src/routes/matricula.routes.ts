import { Router } from 'express';
import { MatriculaController } from '../controllers/matricula.controller';
import { validateObjectId } from '../middlewares/validate-object-id.middleware';
import { asyncHandler } from '../utils/async-handler';

const router = Router();
const controller = new MatriculaController();

router.get('/', asyncHandler(controller.getAll));
router.get('/aprendiz/:aprendizId', validateObjectId('aprendizId'), asyncHandler(controller.getByAprendiz));
router.get('/:id', validateObjectId(), asyncHandler(controller.getById));
router.post('/', asyncHandler(controller.create));
router.put('/:id', validateObjectId(), asyncHandler(controller.update));
router.delete('/:id', validateObjectId(), asyncHandler(controller.delete));

export default router;
