import { expect, test } from '@playwright/test'

import {
  captureAcceptanceScreenshot,
  createApiFixtureState,
  expectNoViewportOverflow,
  installApiFixture,
  loginTenant,
  monitorPageErrors,
  navigateByLink,
  openNavigationIfNeeded,
} from '../fixtures/api'

test('tenant selection, trusted menus, and audience isolation', async ({ page }, testInfo) => {
  const state = createApiFixtureState({ includeUnknownMenu: true, includeUnsafeMenu: true })
  const errors = monitorPageErrors(page)
  await installApiFixture(page, state)
  await loginTenant(page, 'multi@example.test')

  await expect(page.locator('.workspace-summary').getByText('Alpha Team')).toBeVisible()
  await openNavigationIfNeeded(page)
  const navigationRoot = await page.locator('.mobile-navigation-drawer').isVisible()
    ? page.locator('.mobile-navigation-drawer')
    : page.locator('.pa-shell-sidebar')
  await expect(page.getByText('不可信页面')).toHaveCount(0)
  await expect(navigationRoot.getByText('<img src=x onerror=window.__menuInjected=true>', { exact: true })).toBeVisible()
  expect(await page.evaluate(() => (window as Window & { __menuInjected?: boolean }).__menuInjected)).not.toBe(true)

  const storage = await page.evaluate(() => ({
    local: Object.entries(localStorage),
    session: Object.entries(sessionStorage),
    cookies: document.cookie,
  }))
  expect(JSON.stringify(storage)).not.toContain('tenant-access')
  expect(JSON.stringify(storage)).not.toContain('permission_keys')

  await navigateByLink(page, '成员管理')
  await expect(page).toHaveURL(/\/app\/members$/)
  await expect(page.getByText('Member 101')).toBeVisible()
  await expect(page.getByText('core.tenant-owner')).toBeVisible()

  const platformStatus = await page.evaluate(async token => (await fetch('/api/platform/v1/tenants', {
    headers: { Authorization: `Bearer ${token}` },
  })).status, state.tenantToken)
  expect(platformStatus).toBe(401)

  await expectNoViewportOverflow(page)
  await captureAcceptanceScreenshot(page, testInfo, 'tenant-members')
  expect(errors).toEqual([])
})

test('manual unauthorized route does not load protected collection', async ({ page }) => {
  const state = createApiFixtureState({
    tenantPermissions: tenantPermissionsWithout('core.member.read'),
  })
  const errors = monitorPageErrors(page)
  await installApiFixture(page, state)
  await loginTenant(page)

  await page.goto('/app/members')
  await expect(page).toHaveURL(/\/403$/)
  await expect(page.getByText('Access denied')).toBeVisible()
  expect(state.requestCounts.get('GET /api/v1/members') ?? 0).toBe(0)
  expect(errors).toEqual([])
})

const tenantPermissionsWithout = (permission: string): string[] => [
  'core.department.read',
  'core.role.read',
  'core.module.read',
  'core.audit.read',
  'example.target.read',
  'example.reference.read',
  'example.reference.use',
  'example.work-item.read',
  'example.work-item.create',
  'example.work-item.policy-publish',
].filter(item => item !== permission)
