import { defineConfig, devices } from '@playwright/test'

const requiredPort = (name: string): number => {
  const raw = process.env[name]
  if (raw === undefined || !/^[0-9]+$/.test(raw)) throw new Error(`${name}_INVALID`)
  const port = Number(raw)
  if (!Number.isInteger(port) || port < 1 || port > 65535) throw new Error(`${name}_INVALID`)
  return port
}

const shellArgument = (value: string): string => `'${value.replaceAll("'", "'\\''")}'`

const backendPort = requiredPort('MT01_BACKEND_PORT')
const frontendPort = requiredPort('MT01_FRONTEND_PORT')
const generatedHostRoot = process.env.MT01_GENERATED_HOST_ROOT
if (generatedHostRoot === undefined || generatedHostRoot === '') throw new Error('MT01_GENERATED_HOST_ROOT_MISSING')

export default defineConfig({
  testDir: '.',
  fullyParallel: false,
  forbidOnly: true,
  retries: 0,
  workers: 1,
  reporter: [['line']],
  use: {
    baseURL: `http://127.0.0.1:${frontendPort}`,
    serviceWorkers: 'block',
    trace: 'off',
    screenshot: 'off',
    video: 'off',
  },
  projects: [{
    name: 'mt01-admin-web-desktop',
    use: { ...devices['Desktop Chrome'], viewport: { width: 1440, height: 900 } },
  }],
  webServer: [{
    command: `php -d display_errors=0 -d log_errors=0 -S 127.0.0.1:${backendPort} -t ${shellArgument(`${generatedHostRoot}/backend/public`)} tests/mt01/admin-web/fixture/setup.php`,
    cwd: process.env.MT01_REPOSITORY_ROOT,
    url: `http://127.0.0.1:${backendPort}/health`,
    reuseExistingServer: false,
    stdout: 'ignore',
    stderr: 'ignore',
    timeout: 120_000,
  }, {
    command: 'pnpm exec vite --config tests/mt01/admin-web/fixture/vite.config.ts',
    cwd: process.env.MT01_REPOSITORY_ROOT,
    url: `http://127.0.0.1:${frontendPort}/login`,
    reuseExistingServer: false,
    stdout: 'ignore',
    stderr: 'ignore',
    timeout: 120_000,
  }],
})
