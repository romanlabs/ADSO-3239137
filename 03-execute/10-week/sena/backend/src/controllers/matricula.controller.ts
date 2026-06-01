import { Request, Response } from 'express';
import { MatriculaService } from '../services/matricula.service';
import { successResponse } from '../utils/api-response';
import { validateCreateMatricula, validateUpdateMatricula } from '../validators/matricula.validator';

export class MatriculaController {
  private readonly service = new MatriculaService();

  getAll = async (req: Request, res: Response) => {
    const matriculas = await this.service.getAll(req.query);
    res.json(successResponse(matriculas, 'Matriculas obtenidas correctamente'));
  };

  getById = async (req: Request, res: Response) => {
    const matricula = await this.service.getById(String(req.params.id));
    res.json(successResponse(matricula, 'Matricula obtenida correctamente'));
  };

  getByAprendiz = async (req: Request, res: Response) => {
    const matriculas = await this.service.getByAprendiz(String(req.params.aprendizId));
    res.json(successResponse(matriculas, 'Matriculas del aprendiz obtenidas correctamente'));
  };

  create = async (req: Request, res: Response) => {
    const payload = validateCreateMatricula(req.body);
    const matricula = await this.service.create(payload);
    res.status(201).json(successResponse(matricula, 'Matricula creada correctamente'));
  };

  update = async (req: Request, res: Response) => {
    const payload = validateUpdateMatricula(req.body);
    const matricula = await this.service.update(String(req.params.id), payload);
    res.json(successResponse(matricula, 'Matricula actualizada correctamente'));
  };

  delete = async (req: Request, res: Response) => {
    await this.service.delete(String(req.params.id));
    res.json(successResponse(null, 'Matricula eliminada correctamente'));
  };
}
