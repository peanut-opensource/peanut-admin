import { createProtectedFetch, useTenantAuth } from '@peanut-admin/admin/core'
import type { AudienceApiClient } from '@peanut-admin/admin/core'

import { createFixtureRecordModule } from './modules/fixture-record'

export interface AppModuleOptions {
  tenantClient: AudienceApiClient
}

export const createAppModules = (_options: AppModuleOptions) => {
  const auth = useTenantAuth()
  const origin = globalThis.location.origin
  const fixtureFetch = createProtectedFetch({
    baseUrl: origin,
    allowedOrigin: origin,
    getAccessToken: () => auth.accessToken,
    setAccessToken: token => auth.replaceAccessToken(token),
    refresh: async () => null,
    refreshScope: 'fixture-web:tenant',
    isAllowedPath: pathname => pathname.startsWith('/api/fixture/v1/'),
    isCredentialExchange: () => false,
  })

  return [createFixtureRecordModule({
    async list(signal) {
      const response = await fixtureFetch(new Request(new URL('/api/fixture/v1/records', origin), {
        headers: { Accept: 'application/json' },
        method: 'GET',
        signal,
      }))
      const text = await response.text()
      let body: unknown = null
      if (text !== '') {
        try {
          body = JSON.parse(text) as unknown
        } catch {
          body = null
        }
      }
      return {
        body,
        requestId: response.headers.get('X-Request-Id'),
        status: response.status,
      }
    },
  })] as const
}
