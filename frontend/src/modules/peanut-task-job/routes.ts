import { defineAdminModule, hasPermission, useTenantContext } from '@peanut-admin/admin-core'
import {
  TASK_JOB_MANAGE_PERMISSION,
  TASK_JOB_MODULE_KEY,
  TASK_JOB_READ_PERMISSION,
  TASK_JOB_ROUTE_NAME,
  TASK_JOB_ROUTE_PATH,
  TASK_JOB_STORE_KEY,
} from '@peanut-admin/task-job'
import type { TaskJobRuntime, TaskJobTransport, TaskTransportResult } from '@peanut-admin/task-job'
import { defineComponent, h, provide } from 'vue'

interface ApiClientResult { readonly data?: unknown; readonly error?: unknown; readonly response: Response }

const apiClient = async () => {
  const { useAdminRuntime } = await import('../../app/runtime')
  return useAdminRuntime().tenantClient
}

const result = (value: ApiClientResult): TaskTransportResult => ({
  body: value.response.ok ? value.data : value.error,
  headers: value.response.headers,
  status: value.response.status,
})

const transport: TaskJobTransport = {
  async list(status, page, pageSize, signal) {
    return result(await (await apiClient()).GET('/api/v1/tasks', {
      params: { query: { status, page, page_size: pageSize } }, signal,
    }))
  },
  async cancel(jobKey, revision, signal) {
    return result(await (await apiClient()).POST('/api/v1/tasks/{job_key}/cancel', {
      params: { path: { job_key: jobKey }, header: { 'If-Match': `"rev-${revision}"` } }, signal,
    }))
  },
  async retry(jobKey, revision, signal) {
    return result(await (await apiClient()).POST('/api/v1/tasks/{job_key}/retry', {
      params: { path: { job_key: jobKey }, header: { 'If-Match': `"rev-${revision}"` } }, signal,
    }))
  },
}

let runtime: TaskJobRuntime | null = null

const loadTaskJobRoute = async () => {
  const taskJob = await import('@peanut-admin/task-job')
  const active = runtime ?? taskJob.createTaskJobRuntime({
    transport,
    canRead: () => hasPermission(useTenantContext().permissionSet, TASK_JOB_READ_PERMISSION),
    canManage: () => hasPermission(useTenantContext().permissionSet, TASK_JOB_MANAGE_PERMISSION),
  })
  runtime = active
  const contribution = taskJob.createTaskJobModuleContribution(active)
  const route = contribution.routes[0]
  if (contribution.key !== TASK_JOB_MODULE_KEY || contribution.routes.length !== 1
    || route?.name !== TASK_JOB_ROUTE_NAME || route.path !== TASK_JOB_ROUTE_PATH
  ) throw new Error('PEANUT_TASK_JOB_CONTRIBUTION_INVALID')
  const { default: Page } = await route.component()
  return { default: defineComponent({ setup() { provide(taskJob.taskJobRuntimeKey, active); return () => h(Page) } }) }
}

export const peanutTaskJobModule = defineAdminModule({
  key: TASK_JOB_MODULE_KEY,
  routes: [{
    name: TASK_JOB_ROUTE_NAME,
    path: TASK_JOB_ROUTE_PATH,
    component: loadTaskJobRoute,
    access: { moduleKey: TASK_JOB_MODULE_KEY, permissionKeys: [TASK_JOB_READ_PERMISSION] },
  }],
  disposeOnTenantChange: true,
  stores: [{ key: TASK_JOB_STORE_KEY, dispose() { runtime?.dispose(); runtime = null } }],
})
