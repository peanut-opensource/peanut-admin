import { hasAllPermissions, hasPermission, usePlatformContext, useTenantContext } from '@peanut-admin/admin-core'
import { createRouter, createWebHistory } from 'vue-router'
import type { RouteLocationNormalized, RouteRecordRaw } from 'vue-router'

import { exampleReferenceModule } from '../modules/example-reference'
import { exampleTargetModule } from '../modules/example-target'
import { exampleWorkItemModule } from '../modules/example-work-item'
import { AdminApiError } from './runtime'
import type { AdminRuntime } from './runtime'
import { safeReturnTo } from './routes'
import { useWorkspaceStore } from './store'

const moduleRoutes: RouteRecordRaw[] = [exampleTargetModule, exampleReferenceModule, exampleWorkItemModule]
  .flatMap(module => module.routes.map((route): RouteRecordRaw => ({
    path: route.path.slice('/app/'.length),
    name: route.name,
    component: route.component,
    meta: {
      audience: 'tenant' as const,
      permissions: route.access.permissionKeys,
      moduleKey: route.access.moduleKey,
      ...(route.name === 'example-work-item-policy' ? { title: '目标策略发布' } : {}),
    },
  })))

const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'tenant.login',
    component: () => import('../pages/auth/TenantLoginPage.vue'),
    meta: { title: '租户登录', publicAudience: 'tenant' },
  },
  {
    path: '/select-tenant',
    name: 'tenant.select',
    component: () => import('../pages/auth/TenantSelectPage.vue'),
    meta: { title: '选择租户', publicAudience: 'tenant' },
  },
  {
    path: '/platform/login',
    name: 'platform.login',
    component: () => import('../pages/auth/PlatformLoginPage.vue'),
    meta: { title: '平台登录', publicAudience: 'platform' },
  },
  {
    path: '/app',
    component: () => import('../shell/WorkspaceLayout.vue'),
    meta: { audience: 'tenant' },
    children: [
      { path: '', name: 'tenant.home', component: () => import('../pages/common/DashboardPage.vue'), meta: { audience: 'tenant', title: '工作台' } },
      { path: 'account', name: 'tenant.account', component: () => import('../pages/common/AccountPage.vue'), meta: { audience: 'tenant', title: '账号信息' } },
      { path: 'members', name: 'tenant.members.list', component: () => import('../pages/common/ResourceCollectionPage.vue'), meta: { audience: 'tenant', title: '成员管理', permission: 'core.member.read', resourcePage: 'tenant-members' } },
      { path: 'departments', name: 'tenant.departments.list', component: () => import('../pages/common/ResourceCollectionPage.vue'), meta: { audience: 'tenant', title: '部门管理', permission: 'core.department.read', resourcePage: 'tenant-departments' } },
      { path: 'roles', name: 'tenant.roles.list', component: () => import('../pages/common/ResourceCollectionPage.vue'), meta: { audience: 'tenant', title: '角色管理', permission: 'core.role.read', resourcePage: 'tenant-roles' } },
      { path: 'modules', name: 'tenant.modules.list', component: () => import('../pages/common/ResourceCollectionPage.vue'), meta: { audience: 'tenant', title: '模块管理', permission: 'core.module.read', resourcePage: 'tenant-modules' } },
      { path: 'audit', name: 'tenant.audit.list', component: () => import('../pages/common/ResourceCollectionPage.vue'), meta: { audience: 'tenant', title: '审计日志', permission: 'core.audit.read', resourcePage: 'tenant-audit' } },
      ...moduleRoutes,
    ],
  },
  {
    path: '/platform',
    component: () => import('../shell/WorkspaceLayout.vue'),
    meta: { audience: 'platform' },
    children: [
      { path: '', name: 'platform.home', component: () => import('../pages/common/DashboardPage.vue'), meta: { audience: 'platform', title: '平台工作台' } },
      { path: 'tenants', name: 'platform.tenants.list', component: () => import('../pages/common/ResourceCollectionPage.vue'), meta: { audience: 'platform', title: '租户管理', permission: 'platform.tenant.read', resourcePage: 'platform-tenants' } },
      { path: 'tenants/:tenant_id', name: 'platform.tenants.detail', component: () => import('../pages/platform/TenantDetailPage.vue'), meta: { audience: 'platform', title: '租户详情', permission: 'platform.tenant.read' } },
      { path: 'operators', name: 'platform.operators.list', component: () => import('../pages/common/ResourceCollectionPage.vue'), meta: { audience: 'platform', title: '平台操作员', permission: 'platform.operator.read', resourcePage: 'platform-operators' } },
      { path: 'roles', name: 'platform.roles.list', component: () => import('../pages/common/ResourceCollectionPage.vue'), meta: { audience: 'platform', title: '平台角色', permission: 'platform.role.read', resourcePage: 'platform-roles' } },
      { path: 'audit', name: 'platform.audit.list', component: () => import('../pages/common/ResourceCollectionPage.vue'), meta: { audience: 'platform', title: '平台审计', permission: 'platform.audit.read', resourcePage: 'platform-audit' } },
    ],
  },
  { path: '/403', name: 'state.forbidden', component: () => import('../pages/status/StatusPage.vue'), meta: { title: '无权访问' } },
  { path: '/service-unavailable', name: 'state.unavailable', component: () => import('../pages/status/StatusPage.vue'), meta: { title: '服务暂不可用' } },
  { path: '/404', name: 'state.not-found', component: () => import('../pages/status/StatusPage.vue'), meta: { title: '页面不存在' } },
  { path: '/', redirect: '/app' },
  { path: '/:pathMatch(.*)*', redirect: '/404' },
]

const protectedReturnTo = (route: RouteLocationNormalized): string => {
  const audience = route.meta.audience ?? 'tenant'
  return safeReturnTo(route.fullPath, audience)
}

export const createAdminRouter = (runtime: AdminRuntime) => {
  const router = createRouter({ history: createWebHistory(), routes })

  router.beforeEach(async to => {
    const workspace = useWorkspaceStore()
    if (to.meta.publicAudience !== undefined) {
      await runtime.enterAudience(to.meta.publicAudience)
      return true
    }
    const audience = to.meta.audience
    if (audience === undefined) return true

    workspace.booting = true
    try {
      await runtime.enterAudience(audience)
      await runtime.ensureContext(audience)
      const context = audience === 'tenant' ? useTenantContext() : usePlatformContext()
      if (context.value === null) throw new Error('CONTEXT_STALE_RESPONSE')
      await runtime.loadMenus(audience)

      if (to.meta.permission !== undefined && !hasPermission(context.permissionSet, to.meta.permission)) {
        return { name: 'state.forbidden' }
      }
      if (to.meta.permissions !== undefined && !hasAllPermissions(context.permissionSet, to.meta.permissions)) {
        return { name: 'state.forbidden' }
      }
      if (audience === 'tenant'
        && to.meta.moduleKey !== undefined
        && !useTenantContext().moduleSet.has(to.meta.moduleKey)) {
        return { name: 'state.unavailable', query: { code: 'MODULE_TENANT_DISABLED' } }
      }

      return true
    } catch (error) {
      if (error instanceof AdminApiError) {
        workspace.problem = error.problem
        if (error.problem.status === 401) {
          const name = audience === 'tenant' ? 'tenant.login' : 'platform.login'
          return { name, query: { return_to: protectedReturnTo(to) } }
        }
        if (error.problem.status === 403) return { name: 'state.forbidden' }
        if (error.problem.status === 404) return { name: 'state.not-found' }
        return { name: 'state.unavailable', query: { code: error.problem.code } }
      }
      return { name: 'state.unavailable', query: { code: 'CLIENT_BOOT_FAILED' } }
    } finally {
      workspace.booting = false
    }
  })

  return router
}
