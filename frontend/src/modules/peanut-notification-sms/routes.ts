import { defineAdminModule, hasPermission, useTenantContext } from '@peanut-admin/admin-core'
import {
  NOTIFICATION_SMS_MODULE_KEY,
  NOTIFICATION_SMS_READ_PERMISSION,
  NOTIFICATION_SMS_ROUTE_NAME,
  NOTIFICATION_SMS_ROUTE_PATH,
  NOTIFICATION_SMS_STORE_KEY,
} from '@peanut-admin/notification-sms'
import type { NotificationRuntime, NotificationTransport, NotificationTransportResult } from '@peanut-admin/notification-sms'
import { defineComponent, h, provide } from 'vue'

interface ApiClientResult { readonly data?: unknown; readonly error?: unknown; readonly response: Response }

const apiClient = async () => {
  const { useAdminRuntime } = await import('../../app/runtime')
  return useAdminRuntime().tenantClient
}

const result = (value: ApiClientResult): NotificationTransportResult => ({
  body: value.response.ok ? value.data : value.error,
  headers: value.response.headers,
  status: value.response.status,
})

const transport: NotificationTransport = {
  async list(status, page, pageSize, signal) {
    return result(await (await apiClient()).GET('/api/v1/notifications', {
      params: { query: { status, page, page_size: pageSize } }, signal,
    }))
  },
  async markRead(messageKey, revision, signal) {
    return result(await (await apiClient()).POST('/api/v1/notifications/{message_key}/read', {
      params: { path: { message_key: messageKey }, header: { 'If-Match': `"rev-${revision}"` } }, signal,
    }))
  },
  async bulk(messageKeys, action, signal) {
    return result(await (await apiClient()).POST('/api/v1/notifications/bulk', {
      body: { message_keys: [...messageKeys], action }, signal,
    }))
  },
}

let runtime: NotificationRuntime | null = null

const loadNotificationRoute = async () => {
  const notification = await import('@peanut-admin/notification-sms')
  const active = runtime ?? notification.createNotificationRuntime({
    transport,
    canRead: () => hasPermission(useTenantContext().permissionSet, NOTIFICATION_SMS_READ_PERMISSION),
  })
  runtime = active
  const contribution = notification.createNotificationModuleContribution(active)
  const route = contribution.routes[0]
  if (contribution.key !== NOTIFICATION_SMS_MODULE_KEY || contribution.routes.length !== 1
    || route?.name !== NOTIFICATION_SMS_ROUTE_NAME || route.path !== NOTIFICATION_SMS_ROUTE_PATH
  ) throw new Error('PEANUT_NOTIFICATION_SMS_CONTRIBUTION_INVALID')
  const { default: Page } = await route.component()
  return { default: defineComponent({ setup() { provide(notification.notificationRuntimeKey, active); return () => h(Page) } }) }
}

export const peanutNotificationSmsModule = defineAdminModule({
  key: NOTIFICATION_SMS_MODULE_KEY,
  routes: [{
    name: NOTIFICATION_SMS_ROUTE_NAME,
    path: NOTIFICATION_SMS_ROUTE_PATH,
    component: loadNotificationRoute,
    access: { moduleKey: NOTIFICATION_SMS_MODULE_KEY, permissionKeys: [NOTIFICATION_SMS_READ_PERMISSION] },
  }],
  disposeOnTenantChange: true,
  stores: [{ key: NOTIFICATION_SMS_STORE_KEY, dispose() { runtime?.dispose(); runtime = null } }],
})
