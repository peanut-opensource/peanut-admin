import { defineAdminModule, hasPermission, useTenantContext } from '@peanut-admin/admin-core'
import { INTEGRATION_SECURITY_MODULE_KEY, INTEGRATION_SECURITY_ROUTE_PATH, INTEGRATION_SECURITY_ROUTE_PERMISSION } from '@peanut-admin/integration-security'
import type { IntegrationSecurityRuntime, IntegrationSecurityTransport, TransportResult } from '@peanut-admin/integration-security'
import { defineComponent, h, provide } from 'vue'

interface ApiResult { readonly data?: unknown; readonly error?: unknown; readonly response: Response }
const client=async()=>(await import('../../app/runtime')).useAdminRuntime().tenantClient
const result=(v:ApiResult):TransportResult=>({body:v.response.ok?v.data:v.error,headers:v.response.headers,status:v.response.status})
const idempotency=()=>`security-${crypto.randomUUID()}`
const transport:IntegrationSecurityTransport={
  async machines(signal){return result(await(await client()).GET('/api/v1/integration-security/machine-identities',{signal}))},
  async createMachine(input,signal){return result(await(await client()).POST('/api/v1/integration-security/machine-identities',{params:{header:{'Idempotency-Key':idempotency()}},body:input,signal}))},
  async rotateMachine(identityKey,revision,signal){return result(await(await client()).POST('/api/v1/integration-security/machine-identities/{identity_key}/rotate',{params:{path:{identity_key:identityKey},header:{'If-Match':`"rev-${revision}"`,'Idempotency-Key':idempotency()}},signal}))},
  async revokeMachine(identityKey,revision,signal){return result(await(await client()).DELETE('/api/v1/integration-security/machine-identities/{identity_key}',{params:{path:{identity_key:identityKey},header:{'If-Match':`"rev-${revision}"`,'Idempotency-Key':idempotency()}},signal}))},
  async webhooks(signal){return result(await(await client()).GET('/api/v1/integration-security/webhooks',{signal}))},
  async createWebhook(input,signal){return result(await(await client()).POST('/api/v1/integration-security/webhooks',{params:{header:{'Idempotency-Key':idempotency()}},body:input,signal}))},
  async rotateWebhook(endpointKey,revision,signal){return result(await(await client()).POST('/api/v1/integration-security/webhooks/{endpoint_key}/rotate-secret',{params:{path:{endpoint_key:endpointKey},header:{'If-Match':`"rev-${revision}"`,'Idempotency-Key':idempotency()}},signal}))},
  async disableWebhook(endpointKey,revision,signal){return result(await(await client()).DELETE('/api/v1/integration-security/webhooks/{endpoint_key}',{params:{path:{endpoint_key:endpointKey},header:{'If-Match':`"rev-${revision}"`,'Idempotency-Key':idempotency()}},signal}))},
  async deliveries(page,pageSize,signal){return result(await(await client()).GET('/api/v1/integration-security/deliveries',{params:{query:{page,page_size:pageSize}},signal}))},
  async deliveryAttempts(deliveryKey,page,pageSize,signal){return result(await(await client()).GET('/api/v1/integration-security/deliveries/{delivery_key}/attempts',{params:{path:{delivery_key:deliveryKey},query:{page,page_size:pageSize}},signal}))},
  async sessions(signal){return result(await(await client()).GET('/api/v1/integration-security/sessions',{signal}))},
  async revokeSession(sessionKey,signal){return result(await(await client()).POST('/api/v1/integration-security/sessions/{session_key}/revoke',{params:{path:{session_key:sessionKey},header:{'Idempotency-Key':idempotency()}},signal}))},
}
const permission=(key:string)=>()=>hasPermission(useTenantContext().permissionSet,key)
let runtime:IntegrationSecurityRuntime|null=null
const load=async()=>{const feature=await import('@peanut-admin/integration-security');const active=runtime??feature.createIntegrationSecurityRuntime({transport,permissions:{canReadMachines:permission('peanut.integration-security.machine.read'),canManageMachines:permission('peanut.integration-security.machine.manage'),canReadWebhooks:permission('peanut.integration-security.webhook.read'),canManageWebhooks:permission('peanut.integration-security.webhook.manage'),canReadDeliveries:permission('peanut.integration-security.delivery.read'),canReadSessions:permission('peanut.integration-security.session.read'),canRevokeSession:permission('peanut.integration-security.session.revoke')}});runtime=active;const route=feature.createIntegrationSecurityModuleContribution(active).routes[0];const{default:Page}=await route.component();return{default:defineComponent({setup(){provide(feature.integrationSecurityRuntimeKey,active);return()=>h(Page)}})}}
export const peanutIntegrationSecurityModule=defineAdminModule({key:INTEGRATION_SECURITY_MODULE_KEY,routes:[{name:'peanut.integration-security.index',path:INTEGRATION_SECURITY_ROUTE_PATH,component:load,access:{moduleKey:INTEGRATION_SECURITY_MODULE_KEY,permissionKeys:[INTEGRATION_SECURITY_ROUTE_PERMISSION]}}],disposeOnTenantChange:true,stores:[{key:'peanut.integration-security.runtime',dispose(){runtime?.dispose();runtime=null}}]})
