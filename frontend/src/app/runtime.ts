import {
  createPlatformApiClient,
  createTenantApiClient,
  disposeTenantState,
  useOperationTargets,
  usePlatformAuth,
  usePlatformContext,
  useTenantAuth,
  useTenantContext,
} from '@peanut-admin/admin-core'
import type {
  ApiAudience,
  AudienceApiClient,
  PlatformContextData,
  ProblemDetails,
  TenantContextData,
} from '@peanut-admin/admin-core'

import {
  authenticatedLogin,
  envelopeData,
  isRecord,
  menuItems,
  problemFromResponse,
  stringArray,
  stringValue,
  tenantLoginResult,
} from './contracts'
import type { AdminMenuItem, TenantLoginResult } from './contracts'
import { createContextGeneration } from './context-generation'
import { useWorkspaceStore } from './store'
import type { WorkspaceIdentity } from './store'

interface FetchResult {
  data?: unknown
  error?: unknown
  response: Response
}

export class AdminApiError extends Error {
  public constructor(
    public readonly problem: ProblemDetails,
    public readonly retryAfter: string | null = null,
  ) {
    super(problem.code)
  }
}

const unwrap = (result: FetchResult): unknown => {
  if (result.response.ok) return result.data
  throw new AdminApiError(
    problemFromResponse(result.error, result.response),
    result.response.headers.get('Retry-After'),
  )
}

const apiBaseUrl = (): string => stringValue(import.meta.env.VITE_API_BASE_URL)
const tenantClientKey = (): string => stringValue(import.meta.env.VITE_TENANT_CLIENT_KEY, 'admin-web')

const endpoint = (path: string): string => `${apiBaseUrl()}${path}`

const responseJson = async (response: Response): Promise<unknown> => {
  const text = await response.text()
  if (text === '') return null
  try {
    return JSON.parse(text) as unknown
  } catch {
    return null
  }
}

export interface TenantContextView {
  context: TenantContextData
  identity: WorkspaceIdentity
}

export interface PlatformContextView {
  context: PlatformContextData
  identity: WorkspaceIdentity
}

const tenantContextView = (value: unknown): TenantContextView => {
  const data = envelopeData(value)
  if (!isRecord(data)) throw new Error('TENANT_CONTEXT_INVALID')
  const account = isRecord(data.account) ? data.account : {}
  const tenant = isRecord(data.tenant) ? data.tenant : {}
  const member = isRecord(data.member) ? data.member : {}
  const accountId = stringValue(account.id, stringValue(data.account_id))
  const tenantId = stringValue(tenant.id, stringValue(data.tenant_id))
  const memberId = stringValue(member.id, stringValue(data.tenant_member_id))
  if (data.audience !== 'tenant' || accountId === '' || tenantId === '' || memberId === '') {
    throw new Error('TENANT_CONTEXT_INVALID')
  }

  return {
    context: {
      audience: 'tenant',
      accountId,
      tenantId,
      memberId,
      moduleKeys: stringArray(data.module_keys),
      permissionKeys: stringArray(data.permission_keys),
      authorizationRevision: stringValue(data.authorization_revision, '1'),
    },
    identity: {
      accountLabel: stringValue(account.display_name, `Account ${accountId}`),
      contextLabel: stringValue(tenant.display_name, `Tenant ${tenantId}`),
      actorLabel: stringValue(member.display_name, `Member ${memberId}`),
    },
  }
}

const platformContextView = (value: unknown): PlatformContextView => {
  const data = envelopeData(value)
  if (!isRecord(data)) throw new Error('PLATFORM_CONTEXT_INVALID')
  const account = isRecord(data.account) ? data.account : {}
  const operator = isRecord(data.operator) ? data.operator : {}
  const accountId = stringValue(account.id, stringValue(data.account_id))
  const operatorId = stringValue(operator.id, stringValue(data.platform_operator_id))
  if (data.audience !== 'platform' || accountId === '' || operatorId === '') {
    throw new Error('PLATFORM_CONTEXT_INVALID')
  }

  return {
    context: {
      audience: 'platform',
      accountId,
      operatorId,
      permissionKeys: stringArray(data.permission_keys),
      authorizationRevision: stringValue(data.authorization_revision, '1'),
    },
    identity: {
      accountLabel: stringValue(account.display_name, `Account ${accountId}`),
      contextLabel: 'Platform control',
      actorLabel: stringValue(operator.display_name, `Operator ${operatorId}`),
    },
  }
}

export interface AdminRuntime {
  tenantClient: AudienceApiClient
  platformClient: AudienceApiClient
  generation: ReturnType<typeof createContextGeneration>
  tenantLogin: (email: string, password: string, tenantCode: string | null) => Promise<TenantLoginResult>
  beginTenantSwitch: () => Promise<TenantLoginResult>
  selectTenant: (challengeToken: string, tenantId: string) => Promise<void>
  platformLogin: (email: string, password: string) => Promise<void>
  ensureContext: (audience: ApiAudience) => Promise<void>
  loadMenus: (audience: ApiAudience, force?: boolean) => Promise<AdminMenuItem[]>
  enterAudience: (audience: ApiAudience) => Promise<void>
  logout: (audience: ApiAudience) => Promise<void>
  unwrap: (result: FetchResult) => unknown
}

let installedRuntime: AdminRuntime | null = null

export const createAdminRuntime = (): AdminRuntime => {
  const tenantAuth = useTenantAuth()
  const platformAuth = usePlatformAuth()
  const tenantContext = useTenantContext()
  const platformContext = usePlatformContext()
  const workspace = useWorkspaceStore()
  const targets = useOperationTargets()
  const generation = createContextGeneration()

  const refresh = async (audience: ApiAudience): Promise<string | null> => {
    const auth = audience === 'tenant' ? tenantAuth : platformAuth
    auth.markRefreshing()
    const path = audience === 'tenant' ? '/api/v1/auth/refresh' : '/api/platform/v1/auth/refresh'
    const response = await fetch(endpoint(path), {
      method: 'POST',
      credentials: 'include',
      headers: { Accept: 'application/json' },
    })
    const body = await responseJson(response)
    if (!response.ok) {
      auth.clear()
      return null
    }
    const data = envelopeData(body)
    if (!isRecord(data) || typeof data.access_token !== 'string') {
      auth.clear()
      return null
    }
    return data.access_token
  }

  const tenantClient = createTenantApiClient({
    baseUrl: apiBaseUrl(),
    getAccessToken: () => tenantAuth.accessToken,
    setAccessToken: token => tenantAuth.replaceAccessToken(token),
    refresh: () => refresh('tenant'),
    refreshScope: `${tenantClientKey()}:tenant`,
  })
  const platformClient = createPlatformApiClient({
    baseUrl: apiBaseUrl(),
    getAccessToken: () => platformAuth.accessToken,
    setAccessToken: token => platformAuth.replaceAccessToken(token),
    refresh: () => refresh('platform'),
    refreshScope: 'platform-web:platform',
  })

  const clearAudience = async (audience: ApiAudience): Promise<void> => {
    generation.advance()
    if (audience === 'tenant') {
      tenantAuth.clear()
      tenantContext.clear()
      workspace.clearTenant()
      targets.clearAll()
      await disposeTenantState()
    } else {
      platformAuth.clear()
      platformContext.clear()
      workspace.clearPlatform()
    }
  }

  const runtime: AdminRuntime = {
    tenantClient,
    platformClient,
    generation,
    unwrap,
    async tenantLogin(email, password, tenantCode) {
      const result = tenantLoginResult(unwrap(await tenantClient.POST('/api/v1/auth/login', {
        body: { email, password, tenant_code: tenantCode },
      })))
      tenantContext.clear()
      workspace.clearTenant()
      generation.advance()
      if (result.state === 'authenticated') {
        tenantAuth.replaceAccessToken(result.accessToken)
      } else {
        workspace.tenantSelection = result
      }
      return result
    },
    async beginTenantSwitch() {
      const result = tenantLoginResult(unwrap(await tenantClient.POST('/api/v1/auth/tenant-switch/challenge')))
      if (result.state !== 'tenant_selection_required') throw new Error('AUTH_RESPONSE_INVALID')
      workspace.tenantSelection = result
      return result
    },
    async selectTenant(challengeToken, tenantId) {
      await disposeTenantState()
      targets.clearAll()
      const result = authenticatedLogin(unwrap(await tenantClient.POST('/api/v1/auth/tenants/select', {
        body: { challenge_token: challengeToken, tenant_id: tenantId },
      })))
      tenantAuth.replaceAccessToken(result.accessToken)
      tenantContext.clear()
      workspace.clearTenant()
      generation.advance()
    },
    async platformLogin(email, password) {
      const result = authenticatedLogin(unwrap(await platformClient.POST('/api/platform/v1/auth/login', {
        body: { email, password },
      })))
      platformAuth.replaceAccessToken(result.accessToken)
      platformContext.clear()
      workspace.clearPlatform()
      generation.advance()
    },
    async ensureContext(audience) {
      if (audience === 'tenant' && tenantContext.value !== null) return
      if (audience === 'platform' && platformContext.value !== null) return
      const ticket = generation.capture()
      if (audience === 'tenant') {
        const view = tenantContextView(unwrap(await tenantClient.GET('/api/v1/auth/context')))
        if (!ticket.isCurrent()) return
        tenantContext.replace(view.context)
        workspace.tenantIdentity = view.identity
      } else {
        const view = platformContextView(unwrap(await platformClient.GET('/api/platform/v1/auth/context')))
        if (!ticket.isCurrent()) return
        platformContext.replace(view.context)
        workspace.platformIdentity = view.identity
      }
    },
    async loadMenus(audience, force = false) {
      const revision = audience === 'tenant'
        ? tenantContext.authorizationRevision
        : platformContext.authorizationRevision
      const currentRevision = audience === 'tenant'
        ? workspace.tenantMenuRevision
        : workspace.platformMenuRevision
      const existing = audience === 'tenant' ? workspace.tenantMenus : workspace.platformMenus
      if (!force && existing.length > 0 && revision === currentRevision) return existing
      const ticket = generation.capture()
      const response = audience === 'tenant'
        ? await tenantClient.GET('/api/v1/menus')
        : await platformClient.GET('/api/platform/v1/menus')
      const menus = menuItems(unwrap(response))
      if (!ticket.isCurrent()) return []
      if (audience === 'tenant') {
        workspace.tenantMenus = menus
        workspace.tenantMenuRevision = revision
      } else {
        workspace.platformMenus = menus
        workspace.platformMenuRevision = revision
      }
      return menus
    },
    async enterAudience(audience) {
      if (workspace.activeAudience !== null && workspace.activeAudience !== audience) {
        await clearAudience(workspace.activeAudience)
      }
      workspace.activeAudience = audience
      workspace.problem = null
    },
    async logout(audience) {
      try {
        if (audience === 'tenant') {
          await tenantClient.POST('/api/v1/auth/logout')
        } else {
          await platformClient.POST('/api/platform/v1/auth/logout')
        }
      } finally {
        await clearAudience(audience)
        workspace.activeAudience = null
      }
    },
  }

  return runtime
}

export const installAdminRuntime = (runtime: AdminRuntime): void => {
  installedRuntime = runtime
}

export const useAdminRuntime = (): AdminRuntime => {
  if (installedRuntime === null) throw new Error('ADMIN_RUNTIME_NOT_INSTALLED')
  return installedRuntime
}
