import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// The app runs on 5173, the origin the API accepts in CORS_ORIGIN.
export default defineConfig({
  plugins: [react()],
  server: { port: 5173, strictPort: true },
  preview: { port: 5173, strictPort: true },
  test: { environment: 'node' },
});
