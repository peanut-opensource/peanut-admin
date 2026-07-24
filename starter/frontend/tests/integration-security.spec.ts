import { existsSync } from 'node:fs'
import { describe, expect, it } from 'vitest'

describe('internal starter Integration Security consumption', () => {
  it('composes the security workspace without disclosing credentials', async () => {
    expect(existsSync(new URL('../src/modules/peanut-integration-security.ts', import.meta.url))).toBe(true)
    const { createStarterModules } = await import('../src/app/modules')
    const host = createStarterModules({
      baseUrl: 'https://starter.example.test', canRead: () => true, canManage: () => false,
      fetch: async () => new Response(JSON.stringify({ data: [], meta: { request_id: 'req_security_starter' } }), { status: 200, headers: { 'Content-Type': 'application/json' } }),
    })
    expect(host.modules.map(module => module.key)).toEqual([
      'example.greeting',
      'peanut.settings',
      'peanut.reference-codes',
      'peanut.file-media',
      'peanut.task-job',
      'peanut.notification-sms',
      'peanut.import-export',
      'peanut.integration-security',
      'peanut.ops-console',
    ])
    expect(host.integrationSecurityModule.routes[0]).toMatchObject({ path: '/app/integration-security' })
    expect(host.integrationSecurityRuntime.state.disclosure).toBeNull()
    host.integrationSecurityRuntime.dispose()
  })
})
