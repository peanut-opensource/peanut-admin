<script setup lang="ts">
import { AdminShell, PlatformShell, ShellBreadcrumb, ShellHeader, ShellSidebar } from '@peanut-admin/admin-shell'
import { computed } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import type { Component } from 'vue'
import type { ApiAudience } from '@peanut-admin/admin-core'

import type { AdminMenuItem } from '../app/contracts'
import { useAdminRuntime } from '../app/runtime'
import { APP_ROUTE_REGISTRY } from '../app/routes'
import { useWorkspaceStore } from '../app/store'

interface NavigationItem {
  key: string
  name: string
  path: string | null
  children: NavigationItem[]
}

const route = useRoute()
const router = useRouter()
const runtime = useAdminRuntime()
const workspace = useWorkspaceStore()
const audience = computed<ApiAudience>(() => route.meta.audience ?? 'tenant')
const shellComponent = computed<Component>(() => audience.value === 'tenant' ? AdminShell : PlatformShell)
const identity = computed(() => audience.value === 'tenant' ? workspace.tenantIdentity : workspace.platformIdentity)
const title = computed(() => typeof route.meta.title === 'string' ? route.meta.title : '工作台')

const convertMenu = (menu: AdminMenuItem): NavigationItem | null => {
  const children = menu.children.map(convertMenu).filter((item): item is NavigationItem => item !== null)
  if (menu.type === 'group') return { key: menu.key, name: menu.name, path: null, children }
  if (menu.route_name === null) return null
  const registration = APP_ROUTE_REGISTRY.get(menu.route_name)
  if (registration === undefined || registration.audience !== audience.value) {
    workspace.addMenuDiagnostic(menu.route_name)
    return null
  }
  return { key: menu.key, name: menu.name, path: registration.path, children }
}

const navigation = computed<NavigationItem[]>(() => {
  const home: NavigationItem = {
    key: `${audience.value}.home`,
    name: '工作台',
    path: audience.value === 'tenant' ? '/app' : '/platform',
    children: [],
  }
  const account: NavigationItem[] = audience.value === 'tenant'
    ? [{ key: 'tenant.account', name: '账号信息', path: '/app/account', children: [] }]
    : []
  const menus = audience.value === 'tenant' ? workspace.tenantMenus : workspace.platformMenus
  return [home, ...account, ...menus.map(convertMenu).filter((item): item is NavigationItem => item !== null)]
})

const isActive = (path: string): boolean => route.path === path || (path !== '/app' && path !== '/platform' && route.path.startsWith(`${path}/`))

const switchTenant = async () => {
  await runtime.beginTenantSwitch()
  await router.push({ name: 'tenant.select', query: { return_to: '/app' } })
}

const logout = async () => {
  const currentAudience = audience.value
  await runtime.logout(currentAudience)
  await router.replace(currentAudience === 'tenant' ? '/login' : '/platform/login')
}

const closeMobileNavigation = () => {
  workspace.mobileNavigationOpen = false
}
</script>

<template>
  <component :is="shellComponent">
    <template #header>
      <ShellHeader>
        <button
          class="mobile-nav-trigger"
          type="button"
          aria-label="打开导航"
          @click="workspace.mobileNavigationOpen = true"
        >
          <span /><span /><span />
        </button>
        <RouterLink
          class="shell-brand"
          :to="audience === 'tenant' ? '/app' : '/platform'"
        >
          <span
            class="brand-mark"
            aria-hidden="true"
          >P</span>
          <span><strong>Peanut Admin</strong><small>{{ audience === 'tenant' ? '租户工作区' : '平台控制面' }}</small></span>
        </RouterLink>
        <div class="shell-context">
          <span>{{ identity?.contextLabel }}</span>
          <strong>{{ identity?.actorLabel }}</strong>
        </div>
        <div class="shell-commands">
          <el-button
            v-if="audience === 'tenant'"
            text
            @click="switchTenant"
          >
            切换租户
          </el-button>
          <el-button
            text
            @click="logout"
          >
            退出
          </el-button>
        </div>
      </ShellHeader>
    </template>

    <template #sidebar>
      <ShellSidebar
        label="主导航"
        :collapsed="workspace.shellCollapsed"
      >
        <nav class="workspace-navigation">
          <template
            v-for="item in navigation"
            :key="item.key"
          >
            <p
              v-if="item.path === null"
              class="navigation-group"
            >
              {{ item.name }}
            </p>
            <RouterLink
              v-else
              :to="item.path"
              :class="['navigation-link', { 'is-active': isActive(item.path) }]"
              @click="closeMobileNavigation"
            >
              <span
                class="navigation-marker"
                aria-hidden="true"
              />
              <span>{{ item.name }}</span>
            </RouterLink>
            <RouterLink
              v-for="child in item.children"
              :key="child.key"
              :to="child.path ?? (audience === 'tenant' ? '/app' : '/platform')"
              :class="['navigation-link', 'is-child', { 'is-active': child.path !== null && isActive(child.path) }]"
              @click="closeMobileNavigation"
            >
              <span
                class="navigation-marker"
                aria-hidden="true"
              />
              <span>{{ child.name }}</span>
            </RouterLink>
          </template>
        </nav>
        <button
          class="sidebar-collapse"
          type="button"
          @click="workspace.shellCollapsed = !workspace.shellCollapsed"
        >
          {{ workspace.shellCollapsed ? '展开' : '收起' }}
        </button>
      </ShellSidebar>
    </template>

    <template #breadcrumb>
      <ShellBreadcrumb label="当前位置">
        <span>{{ audience === 'tenant' ? '租户工作区' : '平台控制面' }}</span>
        <span aria-hidden="true">/</span>
        <strong>{{ title }}</strong>
      </ShellBreadcrumb>
    </template>

    <RouterView />
  </component>

  <el-drawer
    v-model="workspace.mobileNavigationOpen"
    title="Peanut Admin"
    direction="ltr"
    size="min(84vw, 320px)"
    class="mobile-navigation-drawer"
  >
    <nav class="workspace-navigation">
      <template
        v-for="item in navigation"
        :key="`mobile-${item.key}`"
      >
        <p
          v-if="item.path === null"
          class="navigation-group"
        >
          {{ item.name }}
        </p>
        <RouterLink
          v-else
          :to="item.path"
          class="navigation-link"
          @click="closeMobileNavigation"
        >
          <span
            class="navigation-marker"
            aria-hidden="true"
          />
          <span>{{ item.name }}</span>
        </RouterLink>
        <RouterLink
          v-for="child in item.children"
          :key="`mobile-${child.key}`"
          :to="child.path ?? '/app'"
          class="navigation-link is-child"
          @click="closeMobileNavigation"
        >
          <span
            class="navigation-marker"
            aria-hidden="true"
          />
          <span>{{ child.name }}</span>
        </RouterLink>
      </template>
    </nav>
  </el-drawer>
</template>
