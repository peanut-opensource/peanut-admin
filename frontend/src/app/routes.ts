import { createAdminNavigationRegistry } from '@peanut-admin/admin-core'
import type { AdminNavigationRoute, ApiAudience } from '@peanut-admin/admin-core'

import { exampleReferenceModule } from '../modules/example-reference'
import { exampleTargetModule } from '../modules/example-target'
import { exampleWorkItemModule } from '../modules/example-work-item'
import { peanutSettingsModule } from '../modules/peanut-settings'

export interface AppRouteRegistration {
  name: string
  path: string
  audience: ApiAudience
  permission?: string
  moduleKey?: string
}

const registrations: readonly AppRouteRegistration[] = [
  { name: 'tenant.home', path: '/app', audience: 'tenant' },
  { name: 'tenant.account', path: '/app/account', audience: 'tenant' },
  { name: 'tenant.members.list', path: '/app/members', audience: 'tenant', permission: 'core.member.read' },
  { name: 'tenant.members.effective-access', path: '/app/members/:member_id/effective-access', audience: 'tenant', permission: 'core.member.effective-access.read' },
  { name: 'tenant.departments.list', path: '/app/departments', audience: 'tenant', permission: 'core.department.read' },
  { name: 'tenant.roles.list', path: '/app/roles', audience: 'tenant', permission: 'core.role.read' },
  { name: 'tenant.modules.list', path: '/app/modules', audience: 'tenant', permission: 'core.module.read' },
  { name: 'tenant.audit.list', path: '/app/audit', audience: 'tenant', permission: 'core.audit.read' },
  { name: 'platform.home', path: '/platform', audience: 'platform' },
  { name: 'platform.tenants.list', path: '/platform/tenants', audience: 'platform', permission: 'platform.tenant.read' },
  { name: 'platform.tenants.detail', path: '/platform/tenants/:tenant_id', audience: 'platform', permission: 'platform.tenant.read' },
  { name: 'platform.operators.list', path: '/platform/operators', audience: 'platform', permission: 'platform.operator.read' },
  { name: 'platform.roles.list', path: '/platform/roles', audience: 'platform', permission: 'platform.role.read' },
  { name: 'platform.audit.list', path: '/platform/audit', audience: 'platform', permission: 'platform.audit.read' },
]

export const APP_MODULES = [exampleTargetModule, exampleReferenceModule, exampleWorkItemModule, peanutSettingsModule] as const
export const APP_NAVIGATION = createAdminNavigationRegistry({ routes: registrations, modules: APP_MODULES })
export const APP_ROUTE_REGISTRY = new Map<string, AdminNavigationRoute>(
  APP_NAVIGATION.routes().map(route => [route.name, route]),
)

export const audienceForPath = (path: string): ApiAudience | null => {
  const pathname = new URL(path, 'https://peanut-admin.test').pathname
  if (pathname === '/app' || pathname.startsWith('/app/')) return 'tenant'
  if (pathname === '/platform' || pathname.startsWith('/platform/')) return 'platform'
  return null
}

export const safeReturnTo = (value: unknown, audience: ApiAudience): string => {
  const fallback = audience === 'tenant' ? '/app' : '/platform'
  if (typeof value !== 'string' || !value.startsWith('/') || value.startsWith('//')) {
    return fallback
  }
  const url = new URL(value, 'https://peanut-admin.test')
  if (url.origin !== 'https://peanut-admin.test' || audienceForPath(url.pathname) !== audience) {
    return fallback
  }
  if (url.pathname === '/platform/login') {
    return fallback
  }

  return `${url.pathname}${url.search}${url.hash}`
}

export const resolveMenuDestination = (menu: {
  route_name?: unknown
  route_path?: unknown
  component?: unknown
}): string | null => {
  return APP_NAVIGATION.resolveMenu(menu)?.path ?? null
}
