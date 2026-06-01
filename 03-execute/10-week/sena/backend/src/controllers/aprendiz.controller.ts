import { Request, Response } from 'express';
import { AprendizService } from '../services/aprendiz.service';
import { successResponse } from '../utils/api-response';
import { validateCreateAprendiz, validateUpdateAprendiz } from '../validators/aprendiz.validator';

export class AprendizController {
  private readonly service = new AprendizService();

  getAll = async (req: Request, res: Response) => {
    const aprendices = await this.service.getAll(req.query);
    res.json(successResponse(aprendices, 'Aprendices obtenidos correctamente'));
  };

  getById = async (req: Request, res: Response) => {
    const aprendiz = await this.service.getById(String(req.params.id));
    res.json(successResponse(aprendiz, 'Aprendiz obtenido correctamente'));
  };

  create = async (req: Request, res: Response) => {
    const payload = validateCreateAprendiz(req.body);
    const aprendiz = await this.service.create(payload);
    res.status(201).json(successResponse(aprendiz, 'Aprendiz creado correctamente'));
  };

  update = async (req: Request, res: Response) => {
    const payload = validateUpdateAprendiz(req.body);
    const aprendiz = await this.service.update(String(req.params.id), payload);
    res.json(successResponse(aprendiz, 'Aprendiz actualizado correctamente'));
  };

  delete = async (req: Request, res: Response) => {
    await this.service.delete(String(req.params.id));
    res.json(successResponse(null, 'Aprendiz eliminado correctamente'));
  };
}
