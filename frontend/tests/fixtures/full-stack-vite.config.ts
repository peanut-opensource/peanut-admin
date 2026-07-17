import { defineConfig } from 'vite'

export default defineConfig({
  preview: {
    host: '127.0.0.1',
    port: 4173,
    strictPort: true,
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:4180',
      },
    },
  },
})
