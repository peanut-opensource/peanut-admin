import * as Y from 'yjs'
import { WebsocketProvider } from 'y-websocket'
import type { CollaborationTransport, CollaborationTransportAdmission, CollaborationTransportStatus } from './contracts'

interface ProviderStatusEvent { readonly status: CollaborationTransportStatus }
interface CollaborationWebsocketProvider {
  connect: () => void
  disconnect: () => void
  destroy: () => void
  on: (event: 'status', listener: (event: ProviderStatusEvent) => void) => void
  off: (event: 'status', listener: (event: ProviderStatusEvent) => void) => void
}

export interface CollaborationWebsocketProviderOptions {
  readonly connect: false
  readonly disableBc: true
}

export type CollaborationWebsocketProviderFactory = (
  websocketUrl: string,
  roomName: string,
  document: Y.Doc,
  options: CollaborationWebsocketProviderOptions,
) => CollaborationWebsocketProvider

export interface YWebsocketCollaborationTransportOptions {
  readonly hostOrigin?: string
  readonly providerFactory?: CollaborationWebsocketProviderFactory
}

const providerFactory: CollaborationWebsocketProviderFactory = (websocketUrl, roomName, document, options) => new WebsocketProvider(
  websocketUrl,
  roomName,
  document,
  options,
)

const loopback = (hostname: string): boolean => hostname === 'localhost' || hostname === '127.0.0.1' || hostname === '[::1]'

const validateAdmission = (admission: CollaborationTransportAdmission, hostOrigin: string): URL => {
  let websocket: URL
  let host: URL
  try {
    websocket = new URL(admission.websocketUrl)
    host = new URL(hostOrigin)
  } catch {
    throw new Error('COLLABORATION_TRANSPORT_INVALID')
  }
  const expectedProtocol = host.protocol === 'https:' ? 'wss:' : host.protocol === 'http:' ? 'ws:' : ''
  if ((websocket.protocol !== 'wss:' && websocket.protocol !== 'ws:') || websocket.protocol !== expectedProtocol
    || websocket.hostname !== host.hostname || websocket.port !== host.port || websocket.username !== '' || websocket.password !== ''
    || websocket.search !== '' || websocket.hash !== '' || (websocket.protocol === 'ws:' && !loopback(websocket.hostname))
    || !/^[a-z0-9][a-z0-9._-]{0,127}$/.test(admission.roomName)) {
    throw new Error('COLLABORATION_TRANSPORT_INVALID')
  }
  return websocket
}

export const createYWebsocketCollaborationTransport = (options: YWebsocketCollaborationTransportOptions = {}): CollaborationTransport => {
  const statusListeners = new Set<(status: CollaborationTransportStatus) => void>()
  const updateListeners = new Set<(update: Uint8Array) => void>()
  const createProvider = options.providerFactory ?? providerFactory
  let provider: CollaborationWebsocketProvider | null = null
  let document: Y.Doc | null = null
  let statusHandler: ((event: ProviderStatusEvent) => void) | null = null
  let updateHandler: ((update: Uint8Array, origin: unknown) => void) | null = null
  let disposed = false

  const notifyStatus = (status: CollaborationTransportStatus): void => {
    for (const listener of statusListeners) listener(status)
  }
  const release = (): void => {
    if (provider !== null && statusHandler !== null) provider.off('status', statusHandler)
    if (document !== null && updateHandler !== null) document.off('update', updateHandler)
    provider?.disconnect()
    provider?.destroy()
    document?.destroy()
    provider = null
    document = null
    statusHandler = null
    updateHandler = null
  }

  return {
    connect(admission, initialSnapshot) {
      if (disposed) throw new Error('COLLABORATION_TRANSPORT_DISPOSED')
      if (initialSnapshot.byteLength < 1 || initialSnapshot.byteLength > 8_388_608) throw new Error('COLLABORATION_TRANSPORT_INVALID')
      const origin = options.hostOrigin ?? globalThis.location?.origin
      if (origin === undefined) throw new Error('COLLABORATION_TRANSPORT_ORIGIN_REQUIRED')
      const websocket = validateAdmission(admission, origin)
      release()
      const syncDocument = new Y.Doc()
      Y.applyUpdate(syncDocument, initialSnapshot)
      const nextProvider = createProvider(websocket.toString(), admission.roomName, syncDocument, { connect: false, disableBc: true })
      const nextStatusHandler = (event: ProviderStatusEvent): void => {
        if (provider !== nextProvider || !['connecting', 'connected', 'disconnected'].includes(event.status)) return
        if (event.status === 'disconnected') nextProvider.disconnect()
        notifyStatus(event.status)
      }
      const nextUpdateHandler = (update: Uint8Array, updateOrigin: unknown): void => {
        if (provider !== nextProvider || updateOrigin !== nextProvider) return
        for (const listener of updateListeners) listener(update.slice())
      }
      provider = nextProvider
      document = syncDocument
      statusHandler = nextStatusHandler
      updateHandler = nextUpdateHandler
      syncDocument.on('update', nextUpdateHandler)
      nextProvider.on('status', nextStatusHandler)
      notifyStatus('connecting')
      nextProvider.connect()
    },
    disconnect() {
      if (disposed) return
      release()
      notifyStatus('disconnected')
    },
    sendUpdate(update) {
      if (disposed) throw new Error('COLLABORATION_TRANSPORT_DISPOSED')
      if (document === null) throw new Error('COLLABORATION_TRANSPORT_DISCONNECTED')
      if (update.byteLength < 1 || update.byteLength > 262_144) throw new Error('COLLABORATION_TRANSPORT_UPDATE_INVALID')
      Y.applyUpdate(document, update)
    },
    onStatus(listener) {
      if (disposed) throw new Error('COLLABORATION_TRANSPORT_DISPOSED')
      statusListeners.add(listener)
      return () => { statusListeners.delete(listener) }
    },
    onUpdate(listener) {
      if (disposed) throw new Error('COLLABORATION_TRANSPORT_DISPOSED')
      updateListeners.add(listener)
      return () => { updateListeners.delete(listener) }
    },
    dispose() {
      if (disposed) return
      release()
      disposed = true
      statusListeners.clear()
      updateListeners.clear()
    },
  }
}
