import express from 'express';
import cors from 'cors';
import aprendizRoutes from './routes/aprendiz.routes';
import matriculaRoutes from './routes/matricula.routes';
import { env } from './config/env';
import { requestLogger } from './middlewares/request-logger.middleware';
import { notFoundHandler } from './middlewares/not-found.middleware';
import { errorHandler } from './middlewares/error.middleware';

const app = express();

app.use(cors({ origin: env.corsOrigin }));
app.use(express.json());
app.use(requestLogger);

app.get('/health', (_req, res) => {
  res.status(200).json({ status: 'ok', timestamp: new Date().toISOString() });
});

app.get('/', (_req, res) => {
  res.json({ message: 'SENA Matricula API - MVP' });
});

app.use('/api/aprendices', aprendizRoutes);
app.use('/api/matriculas', matriculaRoutes);
app.use(notFoundHandler);
app.use(errorHandler);

export default app;
