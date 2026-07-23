export type GovernanceWorkbenchAudience = 'tenant' | 'platform'

export interface GovernanceRoleDraft {
  roleId: string
  revision: number
  permissionKeys: readonly string[]
}

export interface GovernanceAuditDetail {
  id: string
  eventType: string
  action: string
  outcome: 'success' | 'denied' | 'error'
  requestId: string
  occurredAt: string
  metadata: Readonly<Record<string, boolean | number | string | null>>
}

export interface GovernanceWorkbenchSnapshot {
  audience: GovernanceWorkbenchAudience
  roleDraft: GovernanceRoleDraft | null
  auditDetail: GovernanceAuditDetail | null
}

const eventMatchesAudience = (audience: GovernanceWorkbenchAudience, eventType: string): boolean => (
  audience === 'platform'
    ? eventType.startsWith('platform.')
    : eventType.startsWith('tenant.') || eventType.startsWith('account.')
)

export const createGovernanceWorkbenchModel = (audience: GovernanceWorkbenchAudience) => {
  let roleDraft: GovernanceRoleDraft | null = null
  let auditDetail: GovernanceAuditDetail | null = null

  return {
    setRoleDraft(next: GovernanceRoleDraft): void {
      if (!/^[1-9][0-9]*$/.test(next.roleId) || next.revision < 1) throw new Error('GOVERNANCE_ROLE_INVALID')
      roleDraft = { ...next, permissionKeys: [...new Set(next.permissionKeys)].sort() }
    },
    setAuditDetail(next: GovernanceAuditDetail): void {
      if (!eventMatchesAudience(audience, next.eventType)) throw new Error('GOVERNANCE_AUDIENCE_MISMATCH')
      auditDetail = { ...next, metadata: { ...next.metadata } }
    },
    clear(): void {
      roleDraft = null
      auditDetail = null
    },
    snapshot(): GovernanceWorkbenchSnapshot {
      return { audience, roleDraft, auditDetail }
    },
  }
}
