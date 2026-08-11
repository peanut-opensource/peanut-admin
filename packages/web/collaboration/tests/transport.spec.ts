import * as Y from 'yjs'
import { describe, expect, it, vi } from 'vitest'
import { createYWebsocketCollaborationTransport } from '../src/transport'
import type { CollaborationWebsocketProviderFactory } from '../src/transport'

const fakeProvider = () => {
  const listeners = new Set<(event: { status: 'connecting' | 'connected' | 'disconnected' }) => void>()
  return {
    connect: vi.fn(), disconnect: vi.fn(), destroy: vi.fn(),
    on: vi.fn((_event: 'status', listener: (event: { status: 'connecting' | 'connected' | 'disconnected' }) => void) => { listeners.add(listener) }),
    off: vi.fn((_event: 'status', listener: (event: { status: 'connecting' | 'connected' | 'disconnected' }) => void) => { listeners.delete(listener) }),
    emit(status: 'connecting' | 'connected' | 'disconnected') { for (const listener of listeners) listener({ status }) },
  }
}

describe('y-websocket collaboration transport', () => {
  it('permits same-origin wss and disables browser broadcast and implicit connection', () => {
    const provider = fakeProvider(); const factory = vi.fn(() => provider) as unknown as CollaborationWebsocketProviderFactory
    const transport = createYWebsocketCollaborationTransport({ hostOrigin: 'https://admin.example.test', providerFactory: factory })
    transport.connect({ websocketUrl: 'wss://admin.example.test/collaboration', roomName: `session_${'a'.repeat(32)}` }, Y.encodeStateAsUpdate(new Y.Doc()))
    expect(factory).toHaveBeenCalledWith('wss://admin.example.test/collaboration', `session_${'a'.repeat(32)}`, expect.any(Y.Doc), { connect: false, disableBc: true })
    expect(provider.connect).toHaveBeenCalledOnce(); transport.dispose()
  })

  it('rejects cross-origin, secret-bearing and non-loopback insecure endpoints', () => {
    const secure = createYWebsocketCollaborationTransport({ hostOrigin: 'https://admin.example.test', providerFactory: () => fakeProvider() })
    const snapshot = Y.encodeStateAsUpdate(new Y.Doc()); const roomName = `session_${'b'.repeat(32)}`
    expect(() => secure.connect({ websocketUrl: 'wss://other.example.test/collaboration', roomName }, snapshot)).toThrow('COLLABORATION_TRANSPORT_INVALID')
    expect(() => secure.connect({ websocketUrl: 'wss://admin.example.test/collaboration?token=secret', roomName }, snapshot)).toThrow('COLLABORATION_TRANSPORT_INVALID')
    const insecure = createYWebsocketCollaborationTransport({ hostOrigin: 'http://admin.example.test', providerFactory: () => fakeProvider() })
    expect(() => insecure.connect({ websocketUrl: 'ws://admin.example.test/collaboration', roomName }, snapshot)).toThrow('COLLABORATION_TRANSPORT_INVALID')
  })

  it('allows loopback development and stops provider reconnect after disconnect', () => {
    const provider = fakeProvider(); const statuses: string[] = []
    const transport = createYWebsocketCollaborationTransport({ hostOrigin: 'http://localhost:5173', providerFactory: () => provider })
    transport.onStatus(status => { statuses.push(status) })
    transport.connect({ websocketUrl: 'ws://localhost:5173/collaboration', roomName: `session_${'c'.repeat(32)}` }, Y.encodeStateAsUpdate(new Y.Doc()))
    provider.emit('connected'); provider.emit('disconnected')
    expect(statuses).toEqual(['connecting', 'connected', 'disconnected'])
    expect(provider.disconnect).toHaveBeenCalled(); transport.dispose()
  })

  it('delivers only provider-origin updates and does not echo local updates', () => {
    const provider = fakeProvider(); let syncDocument: Y.Doc | undefined
    const factory: CollaborationWebsocketProviderFactory = (_url, _room, document) => { syncDocument = document; return provider }
    const transport = createYWebsocketCollaborationTransport({ hostOrigin: 'https://admin.example.test', providerFactory: factory })
    const delivered = vi.fn(); transport.onUpdate(delivered)
    transport.connect({ websocketUrl: 'wss://admin.example.test/collaboration', roomName: `session_${'d'.repeat(32)}` }, Y.encodeStateAsUpdate(new Y.Doc()))
    const remote = new Y.Doc(); remote.getText('content').insert(0, 'remote')
    Y.applyUpdate(syncDocument!, Y.encodeStateAsUpdate(remote), provider)
    expect(delivered).toHaveBeenCalledOnce()
    const local = new Y.Doc(); local.getText('content').insert(0, 'local')
    transport.sendUpdate(Y.encodeStateAsUpdate(local)); expect(delivered).toHaveBeenCalledOnce()
    remote.destroy(); local.destroy(); transport.dispose()
  })
})
