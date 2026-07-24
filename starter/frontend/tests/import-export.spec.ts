import { existsSync } from 'node:fs'
import { describe, expect, it } from 'vitest'

describe('internal starter Import/Export consumption', () => {
  it('composes the package route and Host transport', async () => {
    expect(existsSync(new URL('../src/modules/peanut-import-export.ts', import.meta.url))).toBe(true)
    const { createStarterModules } = await import('../src/app/modules')
    let request: Request | null = null
    const host = createStarterModules({
      baseUrl: 'https://starter.example.test', canRead: () => true, canManage: () => false,
      fetch: async input => { request = input; return new Response(JSON.stringify({ data: { items: [] }, meta: { request_id: 'req_import_export', page: 1, page_size: 20, total: 0 } }), { status: 200, headers: { 'Content-Type': 'application/json' } }) },
    })
    expect(host.importExportModule.routes[0]).toMatchObject({ name: 'peanut.import-export.list', path: '/app/import-export' })
    await host.importExportRuntime.load()
    expect(request?.url).toContain('/api/v1/import-export/operations?')
    expect(host.importExportRuntime.state.items).toEqual([])
    host.importExportRuntime.dispose()
  })
})
