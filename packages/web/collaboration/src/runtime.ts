import {
  CollaborationRequestError,
  assertCollaborationAdmission,
  assertCollaborationStatePage,
  safeCollaborationError,
} from './contracts'
import type {
  CollaborationEngine,
  CollaborationHostApi,
  CollaborationRuntimeState,
  CollaborationTransport,
} from './contracts'

export interface CollaborationRuntime {
  readonly state: CollaborationRuntimeState
  connect: () => Promise<void>
  reconnect: () => Promise<void>
  disconnect: () => void
  onState: (listener: (state: CollaborationRuntimeState) => void) => () => void
  encodeSnapshot: () => Uint8Array
  encodeStateVector: () => Uint8Array
  dispose: () => void
}

export interface CollaborationRuntimeOptions<TDocument = unknown> {
  readonly host: CollaborationHostApi
  readonly engine: CollaborationEngine<TDocument>
  readonly transport: CollaborationTransport
}

const aborted = (error: unknown): boolean => error instanceof DOMException && error.name === 'AbortError'

export const createCollaborationRuntime = <TDocument>(options: CollaborationRuntimeOptions<TDocument>): CollaborationRuntime => {
  const listeners = new Set<(state: CollaborationRuntimeState) => void>()
  let current: CollaborationRuntimeState = { status: 'idle', session: null, lease: null, latestSequence: 0, error: null }
  let controller: AbortController | null = null
  let generation = 0
  let disposed = false
  let establishedSessionKey: string | null = null

  const publish = (patch: Partial<CollaborationRuntimeState>): void => {
    current = { ...current, ...patch }
    for (const listener of listeners) listener(current)
  }
  const disconnect = (): void => {
    if (disposed) return
    generation += 1
    controller?.abort()
    controller = null
    options.transport.disconnect()
    publish({ status: 'disconnected', lease: null, error: null })
  }
  const hydrate = async (sessionKey: string, firstSequence: number, signal: AbortSignal, run: number): Promise<number> => {
    let cursor = firstSequence
    for (let pages = 0; pages < 1000; pages += 1) {
      const page = await options.host.state(sessionKey, cursor, signal)
      if (run !== generation) return cursor
      assertCollaborationStatePage(page, cursor)
      if (page.snapshot !== null) {
        options.engine.applyUpdate(page.snapshot.snapshot, 'replay')
        cursor = page.snapshot.coveredSequence
      }
      for (const update of page.updates) {
        options.engine.applyUpdate(update.payload, 'replay')
        cursor = update.sequence
      }
      if (page.nextAfterSequence === null) return page.latestSequence
      cursor = page.nextAfterSequence
    }
    throw new CollaborationRequestError('COLLABORATION_INTERNAL_ERROR')
  }
  const connect = async (): Promise<void> => {
    if (disposed) throw new Error('COLLABORATION_RUNTIME_DISPOSED')
    const run = ++generation
    controller?.abort()
    options.transport.disconnect()
    const nextController = new AbortController()
    controller = nextController
    publish({ status: 'admitting', lease: null, error: null })
    try {
      const admission = await options.host.admit(nextController.signal)
      if (run !== generation) return
      assertCollaborationAdmission(admission)
      if (establishedSessionKey !== null && establishedSessionKey !== admission.session.sessionKey) {
        throw new CollaborationRequestError('COLLABORATION_CONFLICT')
      }
      const initialSequence = establishedSessionKey === null ? 0 : current.latestSequence
      publish({ status: 'hydrating', session: admission.session, lease: admission.lease, latestSequence: initialSequence })
      const latestSequence = await hydrate(admission.session.sessionKey, initialSequence, nextController.signal, run)
      if (run !== generation) return
      if (latestSequence !== admission.session.latestSequence) throw new CollaborationRequestError('COLLABORATION_INTEGRITY_FAILURE')
      establishedSessionKey = admission.session.sessionKey
      publish({ status: 'connecting', latestSequence })
      options.transport.connect(admission.transport, options.engine.encodeSnapshot())
    } catch (error) {
      if (run !== generation || aborted(error)) return
      options.transport.disconnect()
      publish({ status: 'error', lease: null, error: safeCollaborationError(error) })
    } finally {
      if (controller === nextController) controller = null
    }
  }

  const removeEngineListener = options.engine.onUpdate((update, origin) => {
    if ((current.status === 'connecting' || current.status === 'connected') && origin === 'local') {
      try {
        if (update.byteLength > 262_144) throw new CollaborationRequestError('COLLABORATION_PAYLOAD_TOO_LARGE')
        options.transport.sendUpdate(update)
      } catch (error) {
        options.transport.disconnect()
        publish({ status: 'error', lease: null, error: safeCollaborationError(error) })
      }
    }
  })
  const removeTransportUpdateListener = options.transport.onUpdate(update => {
    if (current.status !== 'connecting' && current.status !== 'connected') return
    try { options.engine.applyUpdate(update, 'remote') } catch (error) {
      options.transport.disconnect()
      publish({ status: 'error', lease: null, error: safeCollaborationError(error) })
    }
  })
  const removeTransportStatusListener = options.transport.onStatus(status => {
    if (disposed || current.status === 'disposed' || current.status === 'error') return
    if (status === 'connected') publish({ status: 'connected', error: null })
    else if (status === 'connecting') publish({ status: 'connecting' })
    else publish({ status: 'disconnected', lease: null })
  })

  return {
    get state() { return current },
    connect,
    async reconnect() { disconnect(); await connect() },
    disconnect,
    onState(listener) {
      if (disposed) throw new Error('COLLABORATION_RUNTIME_DISPOSED')
      listeners.add(listener)
      return () => { listeners.delete(listener) }
    },
    encodeSnapshot: () => options.engine.encodeSnapshot(),
    encodeStateVector: () => options.engine.encodeStateVector(),
    dispose() {
      if (disposed) return
      generation += 1
      controller?.abort()
      controller = null
      removeEngineListener()
      removeTransportUpdateListener()
      removeTransportStatusListener()
      options.transport.dispose()
      options.engine.dispose()
      disposed = true
      current = { ...current, status: 'disposed', lease: null, error: null }
      for (const listener of listeners) listener(current)
      listeners.clear()
    },
  }
}
