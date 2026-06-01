import { NextFunction, Request, Response } from 'express';
import { Types } from 'mongoose';

export function validateObjectId(paramName = 'id') {
  return (req: Request, res: Response, next: NextFunction) => {
    const value = String(req.params[paramName] || '');

    if (!Types.ObjectId.isValid(value)) {
      res.status(400).json({
        success: false,
        error: `Parametro ${paramName} invalido`,
      });
      return;
    }

    next();
  };
}
