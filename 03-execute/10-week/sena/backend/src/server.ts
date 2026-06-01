import app from './app';
import { connectDatabase } from './config/database';
import { env } from './config/env';

connectDatabase()
  .then(() => {
    app.listen(env.port, () => {
      console.log(`Servidor corriendo en puerto ${env.port}`);
    });
  })
  .catch((error) => {
    console.error('Error al conectar a MongoDB:', error);
    process.exit(1);
  });
