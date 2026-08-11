export const COLLABORATION_ENGINE_NAME = 'yjs' as const
export const COLLABORATION_ENGINE_VERSION = '13.6.32' as const

export type CollaborationCapability = 'read' | 'write'
export type CollaborationSessionState = 'active' | 'published' | 'closed' | 'expired'
export type CollaborationConnectionStatus = 'idle' | 'admitting' | 'hydrating' | 'connecting' | 'connected' | 'disconnected' | 'error' | 'disposed'
export type CollaborationUpdateOrigin = 'local' | 'remote' | 'replay'
export type CollaborationTransportStatus = 'connecting' | 'connected' | 'disconnected'
export type CollaborationErrorCode =
  | 'COLLABORATION_INVALID'
  | 'COLLABORATION_NOT_FOUND'
  | 'COLLABORATION_DENIED'
  | 'COLLABORATION_CONFLICT'
  | 'COLLABORATION_LEASE_EXPIRED'
  | 'COLLABORATION_PAYLOAD_TOO_LARGE'
  | 'COLLABORATION_BACKPRESSURE'
  | 'COLLABORATION_PROVIDER_UNAVAILABLE'
  | 'COLLABORATION_INTEGRITY_FAILURE'
  | 'COLLABORATION_INTERNAL_ERROR'

export interface CollaborationSession {
  readonly sessionKey: string
  readonly artifactType: string
  readonly artifactKey: string
  readonly engineName: typeof COLLABORATION_ENGINE_NAME
  readonly engineVersion: typeof COLLABORATION_ENGINE_VERSION
  readonly baseRevisionKey: string
  readonly baseRevisionDigest: string
  readonly latestSequence: number
  readonly state: CollaborationSessionState
  readonly expiresAt: string
}

export interface CollaborationLease {
  readonly leaseKey: string
  readonly clientKey: string
  readonly capability: CollaborationCapability
  readonly expiresAt: string
}

export interface CollaborationTransportAdmission {
  readonly websocketUrl: string
  readonly roomName: string
}

export interface CollaborationAdmission {
  readonly session: CollaborationSession
  readonly lease: CollaborationLease
  readonly transport: CollaborationTransportAdmission
}

export interface CollaborationUpdateEnvelope {
  readonly updateKey: string
  readonly sequence: number
  readonly engineName: typeof COLLABORATION_ENGINE_NAME
  readonly engineVersion: typeof COLLABORATION_ENGINE_VERSION
  readonly digest: string
  readonly payload: Uint8Array
}

export interface CollaborationSnapshotEnvelope {
  readonly snapshotKey: string
  readonly coveredSequence: number
  readonly engineName: typeof COLLABORATION_ENGINE_NAME
  readonly engineVersion: typeof COLLABORATION_ENGINE_VERSION
  readonly snapshotDigest: string
  readonly stateVectorDigest: string
  readonly snapshot: Uint8Array
  readonly stateVector: Uint8Array
}

export interface CollaborationStatePage {
  readonly snapshot: CollaborationSnapshotEnvelope | null
  readonly updates: readonly CollaborationUpdateEnvelope[]
  readonly latestSequence: number
  readonly nextAfterSequence: number | null
}

export interface CollaborationSafeError {
  readonly code: CollaborationErrorCode
  readonly message: string
  readonly requestId: string | null
  readonly status: number
}

export interface CollaborationEngine<TDocument = unknown> {
  readonly document: TDocument
  applyUpdate: (update: Uint8Array, origin?: Exclude<CollaborationUpdateOrigin, 'local'>) => void
  encodeStateVector: () => Uint8Array
  encodeSnapshot: () => Uint8Array
  onUpdate: (listener: (update: Uint8Array, origin: CollaborationUpdateOrigin) => void) => () => void
  dispose: () => void
}

export interface CollaborationTransport {
  connect: (admission: CollaborationTransportAdmission, initialSnapshot: Uint8Array) => void
  disconnect: () => void
  sendUpdate: (update: Uint8Array) => void
  onStatus: (listener: (status: CollaborationTransportStatus) => void) => () => void
  onUpdate: (listener: (update: Uint8Array) => void) => () => void
  dispose: () => void
}

export interface CollaborationHostApi {
  admit: (signal: AbortSignal) => Promise<CollaborationAdmission>
  state: (sessionKey: string, afterSequence: number, signal: AbortSignal) => Promise<CollaborationStatePage>
}

export interface CollaborationRuntimeState {
  readonly status: CollaborationConnectionStatus
  readonly session: CollaborationSession | null
  readonly lease: CollaborationLease | null
  readonly latestSequence: number
  readonly error: CollaborationSafeError | null
}

const stableKey = /^[a-z][a-z0-9]*(?:[.-][a-z0-9]+)*$/
const opaqueKey = /^[a-z][a-z0-9]*_[0-9a-f]{32}$/
const sha256 = /^[0-9a-f]{64}$/
const instant = /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/

const printable = (value: string, maximum: number): boolean => value.length >= 1 && value.length <= maximum && /^[\x20-\x7e]+$/.test(value)
const validInstant = (value: string): boolean => instant.test(value) && Number.isFinite(Date.parse(value))
const sequence = (value: number): boolean => Number.isSafeInteger(value) && value >= 0
const bytes = (value: Uint8Array, maximum: number): boolean => value.byteLength >= 1 && value.byteLength <= maximum

export const assertCollaborationAdmission = (admission: CollaborationAdmission): void => {
  const { session, lease, transport } = admission
  if (!opaqueKey.test(session.sessionKey) || !stableKey.test(session.artifactType) || session.artifactType.length > 64
    || !printable(session.artifactKey, 128) || session.engineName !== COLLABORATION_ENGINE_NAME
    || session.engineVersion !== COLLABORATION_ENGINE_VERSION || !printable(session.baseRevisionKey, 128)
    || !sha256.test(session.baseRevisionDigest) || !sequence(session.latestSequence) || session.state !== 'active'
    || !validInstant(session.expiresAt) || !opaqueKey.test(lease.leaseKey) || !printable(lease.clientKey, 128)
    || (lease.capability !== 'read' && lease.capability !== 'write') || !validInstant(lease.expiresAt)
    || typeof transport.websocketUrl !== 'string' || !printable(transport.roomName, 128)) {
    throw new Error('COLLABORATION_RESPONSE_INVALID')
  }
}

export const assertCollaborationStatePage = (page: CollaborationStatePage, afterSequence: number): void => {
  if (!sequence(page.latestSequence) || page.latestSequence < afterSequence
    || (page.nextAfterSequence !== null && (!sequence(page.nextAfterSequence) || page.nextAfterSequence <= afterSequence || page.nextAfterSequence > page.latestSequence))) {
    throw new Error('COLLABORATION_RESPONSE_INVALID')
  }
  let cursor = afterSequence
  if (page.snapshot !== null) {
    const snapshot = page.snapshot
    if (!opaqueKey.test(snapshot.snapshotKey) || !sequence(snapshot.coveredSequence) || snapshot.coveredSequence < afterSequence
      || snapshot.coveredSequence > page.latestSequence || snapshot.engineName !== COLLABORATION_ENGINE_NAME
      || snapshot.engineVersion !== COLLABORATION_ENGINE_VERSION || !sha256.test(snapshot.snapshotDigest)
      || !sha256.test(snapshot.stateVectorDigest) || !bytes(snapshot.snapshot, 8_388_608) || !bytes(snapshot.stateVector, 8_388_608)) {
      throw new Error('COLLABORATION_RESPONSE_INVALID')
    }
    cursor = snapshot.coveredSequence
  }
  for (const update of page.updates) {
    if (!opaqueKey.test(update.updateKey) || update.sequence !== cursor + 1 || update.sequence > page.latestSequence
      || update.engineName !== COLLABORATION_ENGINE_NAME || update.engineVersion !== COLLABORATION_ENGINE_VERSION
      || !sha256.test(update.digest) || !bytes(update.payload, 262_144)) {
      throw new Error('COLLABORATION_RESPONSE_INVALID')
    }
    cursor = update.sequence
  }
  if (page.nextAfterSequence !== null && page.nextAfterSequence !== cursor) throw new Error('COLLABORATION_RESPONSE_INVALID')
  if (page.nextAfterSequence === null && cursor !== page.latestSequence) throw new Error('COLLABORATION_RESPONSE_INVALID')
}

const messages: Readonly<Record<CollaborationErrorCode, string>> = {
  COLLABORATION_INVALID: 'The collaboration request was rejected.',
  COLLABORATION_NOT_FOUND: 'The collaboration session was not found.',
  COLLABORATION_DENIED: 'You do not have access to this collaboration session.',
  COLLABORATION_CONFLICT: 'The collaboration session changed. Reopen it and try again.',
  COLLABORATION_LEASE_EXPIRED: 'The collaboration lease expired. Reconnect to continue.',
  COLLABORATION_PAYLOAD_TOO_LARGE: 'The collaboration update is too large.',
  COLLABORATION_BACKPRESSURE: 'The collaboration session must be saved before more updates can be accepted.',
  COLLABORATION_PROVIDER_UNAVAILABLE: 'The collaboration service is temporarily unavailable.',
  COLLABORATION_INTEGRITY_FAILURE: 'The collaboration update could not be verified.',
  COLLABORATION_INTERNAL_ERROR: 'The collaboration request could not be completed.',
}

const statuses: Readonly<Record<CollaborationErrorCode, number>> = {
  COLLABORATION_INVALID: 422,
  COLLABORATION_NOT_FOUND: 404,
  COLLABORATION_DENIED: 403,
  COLLABORATION_CONFLICT: 409,
  COLLABORATION_LEASE_EXPIRED: 409,
  COLLABORATION_PAYLOAD_TOO_LARGE: 413,
  COLLABORATION_BACKPRESSURE: 429,
  COLLABORATION_PROVIDER_UNAVAILABLE: 503,
  COLLABORATION_INTEGRITY_FAILURE: 500,
  COLLABORATION_INTERNAL_ERROR: 500,
}

export class CollaborationRequestError extends Error {
  readonly safe: CollaborationSafeError

  constructor(code: CollaborationErrorCode, requestId: string | null = null) {
    super(messages[code])
    this.name = 'CollaborationRequestError'
    const safeRequestId = requestId !== null && /^[A-Za-z0-9._-]{1,128}$/.test(requestId) ? requestId : null
    this.safe = { code, message: messages[code], requestId: safeRequestId, status: statuses[code] }
  }
}

export const safeCollaborationError = (error: unknown): CollaborationSafeError => error instanceof CollaborationRequestError
  ? error.safe
  : new CollaborationRequestError('COLLABORATION_INTERNAL_ERROR').safe
