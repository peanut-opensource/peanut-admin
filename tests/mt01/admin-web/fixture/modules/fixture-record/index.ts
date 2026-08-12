import { defineAdminModule } from '@peanut-admin/admin/core'
import { defineComponent, h, provide } from 'vue'
import type { InjectionKey } from 'vue'

export const FIXTURE_RECORD_MODULE_KEY = 'fixture.record'
export const FIXTURE_RECORD_PERMISSION = 'fixture.record.read'
export const FIXTURE_RECORD_ROUTE = '/app/fixture-records'

export interface FixtureRecordTransportResponse {
  body: unknown
  requestId: string | null
  status: number
}

export interface FixtureRecordTransport {
  list: (signal: AbortSignal) => Promise<FixtureRecordTransportResponse>
}

export const fixtureRecordTransportKey: InjectionKey<FixtureRecordTransport> = Symbol(
  'mt01-fixture-record-transport',
)

export const createFixtureRecordModule = (transport: FixtureRecordTransport) => defineAdminModule({
  key: FIXTURE_RECORD_MODULE_KEY,
  routes: [{
    name: 'fixture.record.list',
    path: FIXTURE_RECORD_ROUTE,
    component: async () => {
      const { default: FixtureRecordPage } = await import('./FixtureRecordPage.vue')

      return {
        default: defineComponent({
          name: 'Mt01FixtureRecordHostRoute',
          setup() {
            provide(fixtureRecordTransportKey, transport)
            return () => h(FixtureRecordPage)
          },
        }),
      }
    },
    access: {
      moduleKey: FIXTURE_RECORD_MODULE_KEY,
      permissionKeys: [FIXTURE_RECORD_PERMISSION],
    },
  }],
  disposeOnTenantChange: true,
})
