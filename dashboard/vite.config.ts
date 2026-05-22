import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    react(),
    tailwindcss()
  ],
  base: '/dashboard/',
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost:8080', // Proxy para o servidor PHP local
        changeOrigin: true,
        secure: false
      }
    }
  }
});
