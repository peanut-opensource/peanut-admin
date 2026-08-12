import { expect, test } from '@playwright/test'
import type { APIResponse, Page, Response } from '@playwright/test'

const requiredSecret = (name: string): string => {
  const value = process.env[name]
  if (value === undefined || value === '') throw new Error(`${name}_MISSING`)
  return value
}

const email = requiredSecret('MT01_FIXTURE_EMAIL')
const password = requiredSecret('MT01_FIXTURE_PASSWORD')
const controlKey = requiredSecret('MT01_CONTROL_KEY')
const backendOrigin = `http://127.0.0.1:${requiredSecret('MT01_BACKEND_PORT')}`
const forbiddenOutputFragments = [email, password, controlKey]

interface ObservedResponse {
  method: string
  path: string
  requestId: string | null
  status: number
}

const loginAndSelectAlpha = async (page: Page): Promise<void> => {
  await page.goto('/login')
  await page.getByLabel('邮箱').fill(email)
  await page.getByLabel('密码').fill(password)
  await page.getByRole('button', { name: '登录' }).click()
  await expect(page).toHaveURL(url => url.pathname === '/select-tenant')
  await expect(page.getByText('Alpha Tenant', { exact: true })).toBeVisible()
  await expect(page.getByText('Beta Tenant', { exact: true })).toBeVisible()
  await page.getByText('Alpha Tenant', { exact: true }).click()
  await page.getByRole('button', { name: '进入工作区' }).click()
  await expect(page).toHaveURL(url => url.pathname === '/app')
  await expect(page.getByText('Alpha Tenant', { exact: true })).toBeVisible()
}

const navigateInApp = async (page: Page, path: string): Promise<void> => {
  await page.evaluate(currentPath => {
    globalThis.history.pushState({}, '', currentPath)
    globalThis.dispatchEvent(new PopStateEvent('popstate'))
  }, path)
}

const problemShape = async (response: APIResponse): Promise<Record<string, unknown>> => {
  expect(response.status()).toBe(404)
  expect(response.headers()['content-type']).toContain('application/problem+json')
  expect(response.headers()['x-request-id']).toBeTruthy()
  const body = await response.json() as Record<string, unknown>
  expect(body.code).toBe('RESOURCE_NOT_FOUND')
  return body
}

test('MT01 Admin Web proves explicit Tenant selection and fail-closed external Module access', async ({ page, request }) => {
  const observed: ObservedResponse[] = []
  let tenantAuthorization: string | null = null
  const pageErrors: string[] = []
  const consoleErrors: string[] = []
  page.on('pageerror', error => pageErrors.push(error.message))
  page.on('console', message => {
    if (message.type() === 'error') consoleErrors.push(message.text())
  })
  page.on('response', (response: Response) => {
    const url = new URL(response.url())
    if (!url.pathname.startsWith('/api/')) return
    observed.push({
      method: response.request().method(),
      path: url.pathname,
      requestId: response.headers()['x-request-id'] ?? null,
      status: response.status(),
    })
    const authorization = response.request().headers().authorization
    if (url.pathname === '/api/fixture/v1/records' && authorization?.startsWith('Bearer ') === true) {
      tenantAuthorization = authorization
    }
  })

  await test.step('login requires an explicit choice between two active Tenants', async () => {
    await loginAndSelectAlpha(page)
    expect(observed.some(item => item.path === '/api/v1/auth/context' && item.status < 400)).toBe(true)
  })

  await test.step('the external page reads only the selected Tenant Alpha row', async () => {
    await navigateInApp(page, '/app/fixture-records')
    await expect(page).toHaveURL(url => url.pathname === '/app/fixture-records')
    await expect(page.getByRole('heading', { name: 'Fixture Records' })).toBeVisible()
    await expect(page.getByText('Alpha Row', { exact: true })).toBeVisible()
    await expect(page.getByText('Beta Row', { exact: true })).toHaveCount(0)
  })

  await test.step('cross-Tenant and unknown backend records have identical 404 Problems', async () => {
    const alphaList = observed.find(item => item.path === '/api/fixture/v1/records' && item.status === 200)
    expect(alphaList).toBeDefined()
    expect(tenantAuthorization).not.toBeNull()
    expect(await page.evaluate(() => ({
      local: Object.keys(localStorage),
      session: Object.keys(sessionStorage),
    }))).toEqual({ local: [], session: [] })

    const headers = { Authorization: tenantAuthorization as string }
    const crossTenant = await request.get(`${backendOrigin}/api/fixture/v1/records/2`, { headers })
    const unknown = await request.get(`${backendOrigin}/api/fixture/v1/records/999999`, { headers })
    observed.push({
      method: 'GET',
      path: '/api/fixture/v1/records/2',
      requestId: crossTenant.headers()['x-request-id'] ?? null,
      status: crossTenant.status(),
    }, {
      method: 'GET',
      path: '/api/fixture/v1/records/999999',
      requestId: unknown.headers()['x-request-id'] ?? null,
      status: unknown.status(),
    })
    const crossProblem = await problemShape(crossTenant)
    const unknownProblem = await problemShape(unknown)
    for (const field of ['status', 'code', 'title', 'detail', 'type']) {
      expect(crossProblem[field]).toEqual(unknownProblem[field])
    }
    expect(Object.keys(crossProblem).sort()).toEqual(Object.keys(unknownProblem).sort())
  })

  await test.step('permission removal denies the route before a collection request', async () => {
    const denied = await request.post(`${backendOrigin}/__mt01/permissions/deny`, {
      headers: { 'X-MT01-Control-Key': controlKey },
    })
    expect(denied.status()).toBe(204)

    await navigateInApp(page, '/login')
    await expect(page).toHaveURL(url => url.pathname === '/login')
    await page.getByLabel('邮箱').fill(email)
    await page.getByLabel('密码').fill(password)
    await page.getByRole('button', { name: '登录' }).click()
    await expect(page).toHaveURL(url => url.pathname === '/select-tenant')
    await page.getByText('Alpha Tenant', { exact: true }).click()
    await page.getByRole('button', { name: '进入工作区' }).click()
    await expect(page).toHaveURL(url => url.pathname === '/app')

    const before = observed.filter(item => item.path === '/api/fixture/v1/records').length
    await navigateInApp(page, '/app/fixture-records')
    await expect(page).toHaveURL(url => url.pathname === '/403')
    const after = observed.filter(item => item.path === '/api/fixture/v1/records').length
    expect(after).toBe(before)
  })

  expect(observed.length).toBeGreaterThan(0)
  expect(observed.every(item => item.requestId !== null)).toBe(true)
  for (const output of [...pageErrors, ...consoleErrors]) {
    expect(forbiddenOutputFragments.some(fragment => output.includes(fragment))).toBe(false)
    expect(output).not.toMatch(/pa_(?:lat|lrt|lc|tat|trt)_[A-Za-z0-9_-]+/)
  }
  expect(pageErrors).toEqual([])
  expect(consoleErrors).toEqual([])
})
