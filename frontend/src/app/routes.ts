import type { ApiAudience } from '@peanut-admin/admin-core'

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
  { name: 'example-target-list', path: '/app/examples/targets', audience: 'tenant', permission: 'example.target.read', moduleKey: 'example.target' },
  { name: 'example-reference-list', path: '/app/examples/references', audience: 'tenant', permission: 'example.reference.read', moduleKey: 'example.reference' },
  { name: 'example-work-item-list', path: '/app/examples/work-items', audience: 'tenant', permission: 'example.work-item.read', moduleKey: 'example.work-item' },
  { name: 'example-work-item-policy', path: '/app/examples/work-item-policies', audience: 'tenant', permission: 'example.work-item.policy-publish', moduleKey: 'example.work-item' },
  { name: 'platform.home', path: '/platform', audience: 'platform' },
  { name: 'platform.tenants.list', path: '/platform/tenants', audience: 'platform', permission: 'platform.tenant.read' },
  { name: 'platform.tenants.detail', path: '/platform/tenants/:tenant_id', audience: 'platform', permission: 'platform.tenant.read' },
  { name: 'platform.operators.list', path: '/platform/operators', audience: 'platform', permission: 'platform.operator.read' },
  { name: 'platform.roles.list', path: '/platform/roles', audience: 'platform', permission: 'platform.role.read' },
  { name: 'platform.audit.list', path: '/platform/audit', audience: 'platform', permission: 'platform.audit.read' },
]

export const APP_ROUTE_REGISTRY = new Map(registrations.map(route => [route.name, route]))

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
  if (typeof menu.route_name !== 'string') return null
  return APP_ROUTE_REGISTRY.get(menu.route_name)?.path ?? null
}
