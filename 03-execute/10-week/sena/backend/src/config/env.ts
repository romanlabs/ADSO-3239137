export const env = {
  nodeEnv: process.env.NODE_ENV || 'development',
  port: Number(process.env.PORT || 3001),
  mongoUri: process.env.MONGO_URI || 'mongodb://localhost:27017/sena_matricula',
  corsOrigin: process.env.CORS_ORIGIN || '*',
};
