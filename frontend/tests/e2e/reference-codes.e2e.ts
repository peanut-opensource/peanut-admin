import { expect, test } from '@playwright/test'
import type { Page, Response } from '@playwright/test'

import { browserPassword, monitorFullStackErrors } from '../fixtures/full-stack'

const email = 'browser-owner@example.test'

const loginAndEnterTenant = async (page: Page, tenantName: string): Promise<void> => {
  await page.goto('/login')
  await page.getByLabel('邮箱').fill(email)
  await page.getByLabel('密码').fill(browserPassword())
  await page.getByRole('button', { name: '登录' }).click()
  await expect(page).toHaveURL(url => url.pathname === '/select-tenant')
  await page.getByText(tenantName, { exact: true }).click()
  await page.getByRole('button', { name: '进入工作区' }).click()
  await expect(page).toHaveURL(url => url.pathname === '/app')
}

const referenceResponse = (page: Page, method: string): Promise<Response> => page.waitForResponse(response => {
  const path = new URL(response.url()).pathname
  return response.request().method() === method && path.startsWith('/api/v1/reference-code-sets')
})

const expectNoHorizontalOverflow = async (page: Page): Promise<void> => {
  const dimensions = await page.evaluate(() => ({
    body: document.body.scrollWidth,
    document: document.documentElement.scrollWidth,
    viewport: document.documentElement.clientWidth,
  }))
  expect(Math.max(dimensions.body, dimensions.document)).toBeLessThanOrEqual(dimensions.viewport + 1)
}

const runWorkflow = async (page: Page, tenantName: string, code: string): Promise<void> => {
  const errors = monitorFullStackErrors(page)
  await loginAndEnterTenant(page, tenantName)

  const setsResponse = referenceResponse(page, 'GET')
  await page.goto('/app/reference-codes')
  expect((await setsResponse).status()).toBe(200)
  await page.getByLabel('Owner and set').selectOption({ index: 1 })
  await expect(page.locator('[data-reference-codes-state="loading"]')).toBeHidden()

  await page.getByRole('button', { name: 'Create code' }).click()
  const createDialog = page.getByRole('dialog', { name: 'Create reference code' })
  await createDialog.getByLabel('Code').fill(code)
  await createDialog.getByLabel('Label').fill('Initial label')
  await createDialog.getByLabel('Metadata JSON').fill('{"source":"e2e"}')
  const createResponse = referenceResponse(page, 'POST')
  await createDialog.getByRole('button', { name: 'Create' }).click()
  const created = await createResponse
  expect(created.status()).toBe(201)
  expect(created.request().headers()['if-none-match']).toBe('*')
  const createdEtag = created.headers().etag
  expect(createdEtag).toMatch(/^"rev-[1-9][0-9]*"$/)

  const row = page.locator(`[data-reference-code="${code}"]`)
  await row.getByRole('button', { name: 'Append version' }).click()
  const appendDialog = page.getByRole('dialog', { name: 'Append reference-code version' })
  await expect(appendDialog.getByLabel('Code')).toBeDisabled()
  await appendDialog.getByLabel('Label').fill('Updated label')
  const replaceResponse = referenceResponse(page, 'PUT')
  await appendDialog.getByRole('button', { name: 'Append version' }).click()
  const replaced = await replaceResponse
  expect(replaced.status()).toBe(200)
  expect(replaced.request().headers()['if-match']).toBe(createdEtag)

  await row.getByRole('button', { name: 'Retire' }).click()
  const retireDialog = page.getByRole('dialog', { name: 'Retire reference code' })
  await expect(retireDialog).toContainText('cannot be reused')
  const retireResponse = referenceResponse(page, 'DELETE')
  await retireDialog.getByRole('button', { name: 'Retire permanently' }).click()
  expect((await retireResponse).status()).toBe(200)
  await expect(row.getByText('retired', { exact: true })).toBeVisible()
  await expect(row.getByRole('button', { name: 'Retire' })).toBeDisabled()
  await expectNoHorizontalOverflow(page)
  expect(errors).toEqual([])
}

test('desktop real-backend reference-code workflow', async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 900 })
  await runWorkflow(page, 'Alpha Team', `desktop-${Date.now()}`)
})

test('mobile real-backend reference-code workflow', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 })
  await runWorkflow(page, 'Beta Team', `mobile-${Date.now()}`)
})
