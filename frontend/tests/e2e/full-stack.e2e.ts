import { expect, test } from '@playwright/test'

import {
  browserPassword,
  captureFullStackScreenshot,
  expectApiResponse,
  monitorFullStackErrors,
  observeApi,
} from '../fixtures/full-stack'

const email = 'browser-owner@example.test'

test('real tenant login reaches multi-target read and single-target write', async ({ page }, testInfo) => {
  const responses = observeApi(page)
  const errors = monitorFullStackErrors(page)

  await page.goto('/login')
  await page.getByLabel('邮箱').fill(email)
  await page.getByLabel('密码').fill(browserPassword())
  await page.getByRole('button', { name: '登录' }).click()
  await expect(page).toHaveURL(/\/select-tenant/)
  await expect(page.getByText('Alpha Team')).toBeVisible()
  await expect(page.getByText('Beta Team')).toBeVisible()
  await page.getByRole('button', { name: '进入工作区' }).click()
  await expect(page).toHaveURL(/\/app$/)
  await expect(page.locator('.workspace-summary').getByText('Alpha Team')).toBeVisible()

  await page.goto('/app/examples/work-items')
  await expect(page.getByText('0 of 2 targets selected')).toBeVisible()
  await page.getByRole('button', { name: '选择全部已授权' }).click()
  await expect(page.getByText('2 of 2 targets selected')).toBeVisible()
  await expect(page.getByRole('columnheader', { name: '归属目标' })).toBeVisible()
  await expect(page.getByText('Project A work')).toBeVisible()
  await expect(page.getByText('Project B work')).toBeVisible()

  const selector = page.locator('.pa-target-selector')
  await selector.locator('.el-tag').filter({ hasText: 'Project B' })
    .getByRole('button', { name: 'Close this tag' }).click()
  await expect(page.getByText('1 of 2 targets selected')).toBeVisible()
  await expect(page.getByRole('button', { name: '新建工作项' })).toBeEnabled()
  await page.getByRole('button', { name: '新建工作项' }).click()
  const dialog = page.getByRole('dialog', { name: '新建工作项' })
  await dialog.getByLabel('标题').fill(`Full-stack ${testInfo.project.name}`)
  await dialog.getByRole('combobox').click()
  await page.getByRole('option', { name: 'Public Reference' }).click()
  await dialog.getByRole('button', { name: '创建' }).click()
  await expect(dialog).toBeHidden()
  await expect(page.getByText(`Full-stack ${testInfo.project.name}`)).toBeVisible()

  const storage = await page.evaluate(() => ({
    local: Object.entries(localStorage),
    session: Object.entries(sessionStorage),
  }))
  expect(JSON.stringify(storage)).not.toContain('access_token')
  expect(JSON.stringify(storage)).not.toContain('refresh_token')
  await captureFullStackScreenshot(page, testInfo, 'real-tenant-write')

  expectApiResponse(responses, 'POST', '/api/v1/auth/login', 200)
  expectApiResponse(responses, 'POST', '/api/v1/auth/tenants/select', 200)
  expectApiResponse(responses, 'GET', '/api/v1/auth/context', 200)
  expectApiResponse(responses, 'GET', '/api/v1/authorization/target-candidates', 200)
  expectApiResponse(responses, 'GET', '/api/v1/example/work-items', 200)
  expectApiResponse(responses, 'GET', '/api/v1/example/reference-items/candidates', 200)
  expectApiResponse(responses, 'POST', '/api/v1/example/work-items', 201)
  expect(errors).toEqual([])
})

test('real platform login reaches the protected tenant collection', async ({ page }, testInfo) => {
  const responses = observeApi(page)
  const errors = monitorFullStackErrors(page)

  await page.goto('/platform/login')
  await page.getByLabel('邮箱').fill(email)
  await page.getByLabel('密码').fill(browserPassword())
  await page.getByRole('button', { name: '登录' }).click()
  await expect(page).toHaveURL(/\/platform$/)
  await page.goto('/platform/tenants')
  await expect(page.getByText('Alpha Team').last()).toBeVisible()
  await expect(page.getByText('Beta Team').last()).toBeVisible()
  await captureFullStackScreenshot(page, testInfo, 'real-platform-tenants')

  expectApiResponse(responses, 'POST', '/api/platform/v1/auth/login', 200)
  expectApiResponse(responses, 'GET', '/api/platform/v1/auth/context', 200)
  expectApiResponse(responses, 'GET', '/api/platform/v1/menus', 200)
  expectApiResponse(responses, 'GET', '/api/platform/v1/tenants', 200)
  expect(errors).toEqual([])
})
