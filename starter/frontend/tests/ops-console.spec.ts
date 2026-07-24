import { existsSync } from 'node:fs'
import { describe, expect, it } from 'vitest'

describe('internal starter Ops Console consumption', () => {
  it('registers the platform workbench route with fail-closed permissions', async () => {
    expect(existsSync(new URL('../src/modules/peanut-ops-console.ts', import.meta.url))).toBe(true)
    const { createStarterModules } = await import('../src/app/modules')
    const host = createStarterModules({
      baseUrl: 'https://starter.example.test', canRead: () => true, canManage: () => false,
      fetch: async () => { throw new Error('not called') },
    })
    expect(host.opsConsoleModule.routes[0]).toMatchObject({ name: 'peanut.ops-console.page', path: '/platform/ops', access: { permissionKeys: ['platform.ops.read'] } })
    expect(host.opsConsoleRuntime.canBackup()).toBe(false)
    host.opsConsoleRuntime.dispose()
  })
})
