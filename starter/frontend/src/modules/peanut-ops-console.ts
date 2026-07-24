import { defineAdminModule } from '@peanut-admin/admin-core'
import type { AdminModuleContribution } from '@peanut-admin/admin-core'
import { createOpsConsoleFetchTransport, createOpsConsoleRuntime, OPS_CONSOLE_STORE_KEY, OpsConsolePage, opsConsoleRuntimeKey } from '@peanut-admin/ops-console'
import type { OpsConsoleRuntime, OpsProviderOption } from '@peanut-admin/ops-console'
import { defineComponent, h, provide } from 'vue'

export interface PeanutOpsConsoleHostOptions {
  baseUrl: string
  fetch: (request: Request) => Promise<Response>
  opsProviders?: readonly OpsProviderOption[]
  opsMaintenanceReasons?: readonly string[]
  opsLogSources?: readonly string[]
  canReadOps?: () => boolean
  canBackup?: () => boolean
  canRestore?: () => boolean
  canMaintain?: () => boolean
  canReadLogs?: () => boolean
}
export interface PeanutOpsConsoleHost { module: AdminModuleContribution; runtime: OpsConsoleRuntime }

export const createPeanutOpsConsoleHost = (options: PeanutOpsConsoleHostOptions): PeanutOpsConsoleHost => {
  const denied = (): boolean => false
  const runtime = createOpsConsoleRuntime({
    transport: createOpsConsoleFetchTransport({ baseUrl: options.baseUrl, fetch: options.fetch }),
    providers: options.opsProviders ?? [], maintenanceReasons: options.opsMaintenanceReasons ?? [], logSources: options.opsLogSources ?? [],
    canRead: options.canReadOps ?? denied, canBackup: options.canBackup ?? denied, canRestore: options.canRestore ?? denied,
    canMaintain: options.canMaintain ?? denied, canReadLogs: options.canReadLogs ?? denied,
  })
  const page = defineComponent({ name: 'StarterOpsConsoleHostPage', setup() { provide(opsConsoleRuntimeKey, runtime); return () => h(OpsConsolePage) } })
  const module = defineAdminModule({ key: 'peanut.ops-console', routes: [{ name: 'peanut.ops-console.page', path: '/platform/ops', component: async () => ({ default: page }), access: { permissionKeys: ['platform.ops.read'] } }], stores: [{ key: OPS_CONSOLE_STORE_KEY, dispose: runtime.dispose }] })
  return { module, runtime }
}
