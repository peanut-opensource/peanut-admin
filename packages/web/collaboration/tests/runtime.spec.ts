import { describe, expect, it, vi } from 'vitest'
import { CollaborationRequestError } from '../src/contracts'
import { createCollaborationRuntime } from '../src/runtime'
import type {
  CollaborationAdmission,
  CollaborationEngine,
  CollaborationHostApi,
  CollaborationTransport,
  CollaborationTransportStatus,
  CollaborationUpdateOrigin,
} from '../src/contracts'

const sessionKey = `session_${'a'.repeat(32)}`
const leaseKey = `lease_${'b'.repeat(32)}`
const admission: CollaborationAdmission = {
  session: {
    sessionKey, artifactType: 'content.document', artifactKey: 'article-1', engineName: 'yjs', engineVersion: '13.6.32',
    baseRevisionKey: `revision_${'c'.repeat(32)}`, baseRevisionDigest: 'd'.repeat(64), latestSequence: 1,
    state: 'active', expiresAt: '2026-08-12T12:00:00.000Z',
  },
  lease: { leaseKey, clientKey: 'browser-1', capability: 'write', expiresAt: '2026-08-12T11:05:00.000Z' },
  transport: { websocketUrl: 'wss://admin.example.test/collaboration', roomName: sessionKey },
}

const engine = (): CollaborationEngine<object> & { emit: (update: Uint8Array, origin: CollaborationUpdateOrigin) => void } => {
  const listeners = new Set<(update: Uint8Array, origin: CollaborationUpdateOrigin) => void>()
  return {
    document: {}, applyUpdate: vi.fn(), encodeStateVector: () => new Uint8Array([1]), encodeSnapshot: () => new Uint8Array([2]),
    onUpdate(listener) { listeners.add(listener); return () => { listeners.delete(listener) } },
    dispose: vi.fn(), emit(update, origin) { for (const listener of listeners) listener(update, origin) },
  }
}
const transport = (): CollaborationTransport & { emitStatus: (status: CollaborationTransportStatus) => void; emitUpdate: (update: Uint8Array) => void } => {
  const statuses = new Set<(status: CollaborationTransportStatus) => void>(); const updates = new Set<(update: Uint8Array) => void>()
  return {
    connect: vi.fn(), disconnect: vi.fn(), sendUpdate: vi.fn(), dispose: vi.fn(),
    onStatus(listener) { statuses.add(listener); return () => { statuses.delete(listener) } },
    onUpdate(listener) { updates.add(listener); return () => { updates.delete(listener) } },
    emitStatus(status) { for (const listener of statuses) listener(status) }, emitUpdate(update) { for (const listener of updates) listener(update) },
  }
}

describe('collaboration runtime', () => {
  it('hydrates ordered Host state before connecting and relays updates by origin', async () => {
    const document = engine(); const socket = transport()
    const host: CollaborationHostApi = {
      admit: vi.fn(async () => admission),
      state: vi.fn(async () => ({
        snapshot: null, latestSequence: 1, nextAfterSequence: null,
        updates: [{ updateKey: `update_${'e'.repeat(32)}`, sequence: 1, engineName: 'yjs' as const, engineVersion: '13.6.32' as const, digest: 'f'.repeat(64), payload: new Uint8Array([3]) }],
      })),
    }
    const runtime = createCollaborationRuntime({ host, engine: document, transport: socket })
    await runtime.connect()
    expect(document.applyUpdate).toHaveBeenCalledWith(new Uint8Array([3]), 'replay')
    expect(socket.connect).toHaveBeenCalledWith(admission.transport, new Uint8Array([2]))
    document.emit(new Uint8Array([4]), 'local'); expect(socket.sendUpdate).toHaveBeenCalledWith(new Uint8Array([4]))
    socket.emitUpdate(new Uint8Array([5])); expect(document.applyUpdate).toHaveBeenCalledWith(new Uint8Array([5]), 'remote')
    socket.emitStatus('connected'); expect(runtime.state.status).toBe('connected'); runtime.dispose()
  })

  it('revalidates Host admission before every reconnect', async () => {
    const emptyAdmission: CollaborationAdmission = { ...admission, session: { ...admission.session, latestSequence: 0 } }
    const host: CollaborationHostApi = { admit: vi.fn(async () => emptyAdmission), state: vi.fn(async () => ({ snapshot: null, updates: [], latestSequence: 0, nextAfterSequence: null })) }
    const runtime = createCollaborationRuntime({ host, engine: engine(), transport: transport() })
    await runtime.connect(); await runtime.reconnect()
    expect(host.admit).toHaveBeenCalledTimes(2); runtime.dispose()
  })

  it('exposes only stable safe errors and aborts active admission on disposal', async () => {
    const deniedHost: CollaborationHostApi = { admit: async () => { throw new CollaborationRequestError('COLLABORATION_DENIED', 'req-1') }, state: vi.fn() }
    const denied = createCollaborationRuntime({ host: deniedHost, engine: engine(), transport: transport() })
    await denied.connect(); expect(denied.state.error).toEqual({ code: 'COLLABORATION_DENIED', message: 'You do not have access to this collaboration session.', requestId: 'req-1', status: 403 })
    expect(JSON.stringify(denied.state.error)).not.toContain('token'); denied.dispose()

    let signal: AbortSignal | undefined
    const pendingHost: CollaborationHostApi = { admit: observed => { signal = observed; return new Promise<CollaborationAdmission>(() => {}) }, state: vi.fn() }
    const pending = createCollaborationRuntime({ host: pendingHost, engine: engine(), transport: transport() })
    void pending.connect(); await Promise.resolve(); pending.dispose()
    expect(signal?.aborted).toBe(true); expect(pending.state.status).toBe('disposed')
  })
})
