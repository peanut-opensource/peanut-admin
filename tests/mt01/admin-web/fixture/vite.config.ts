import vue from '@vitejs/plugin-vue'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { defineConfig } from 'vite'

const fixtureRoot = path.dirname(fileURLToPath(import.meta.url))
const repositoryRoot = process.env.MT01_REPOSITORY_ROOT
const generatedHostRoot = process.env.MT01_GENERATED_HOST_ROOT
const backendPort = Number(process.env.MT01_BACKEND_PORT)
const frontendPort = Number(process.env.MT01_FRONTEND_PORT)

if (repositoryRoot === undefined || generatedHostRoot === undefined
  || !Number.isInteger(backendPort) || !Number.isInteger(frontendPort)) {
  throw new Error('MT01_VITE_ENVIRONMENT_INVALID')
}

const frontendRoot = path.join(repositoryRoot, 'frontend')
const generatedWeb = path.join(generatedHostRoot, 'packages/web')

export default defineConfig({
  root: frontendRoot,
  define: {
    'import.meta.env.VITE_TENANT_CLIENT_KEY': JSON.stringify('fixture-web'),
  },
  plugins: [vue()],
  resolve: {
    alias: [
      {
        find: /^\.\/modules$/,
        replacement: path.join(fixtureRoot, 'main.ts'),
      },
      {
        find: '@peanut-admin/admin/core',
        replacement: path.join(generatedWeb, 'admin-core/src/index.ts'),
      },
      {
        find: '@peanut-admin/admin/shell',
        replacement: path.join(generatedWeb, 'admin-shell/src/index.ts'),
      },
    ],
  },
  server: {
    host: '127.0.0.1',
    port: frontendPort,
    strictPort: true,
    proxy: {
      '/api': {
        target: `http://127.0.0.1:${backendPort}`,
        changeOrigin: false,
      },
    },
  },
})
