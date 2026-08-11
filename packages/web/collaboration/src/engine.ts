import * as Y from 'yjs'
import type { CollaborationEngine, CollaborationUpdateOrigin } from './contracts'

const remoteOrigin = Symbol('peanut.collaboration.remote')
const replayOrigin = Symbol('peanut.collaboration.replay')

export type YjsCollaborationEngine = CollaborationEngine<Y.Doc>

export const createYjsCollaborationEngine = (options: { readonly gc?: boolean; readonly guid?: string } = {}): YjsCollaborationEngine => {
  const document = new Y.Doc(options)
  const listeners = new Set<(update: Uint8Array, origin: CollaborationUpdateOrigin) => void>()
  let disposed = false
  const updated = (update: Uint8Array, origin: unknown): void => {
    if (disposed) return
    const kind: CollaborationUpdateOrigin = origin === remoteOrigin ? 'remote' : origin === replayOrigin ? 'replay' : 'local'
    for (const listener of listeners) listener(update.slice(), kind)
  }
  document.on('update', updated)
  return {
    document,
    applyUpdate(update, origin = 'remote') {
      if (disposed) throw new Error('COLLABORATION_ENGINE_DISPOSED')
      Y.applyUpdate(document, update, origin === 'replay' ? replayOrigin : remoteOrigin)
    },
    encodeStateVector() {
      if (disposed) throw new Error('COLLABORATION_ENGINE_DISPOSED')
      return Y.encodeStateVector(document)
    },
    encodeSnapshot() {
      if (disposed) throw new Error('COLLABORATION_ENGINE_DISPOSED')
      return Y.encodeStateAsUpdate(document)
    },
    onUpdate(listener) {
      if (disposed) throw new Error('COLLABORATION_ENGINE_DISPOSED')
      listeners.add(listener)
      return () => { listeners.delete(listener) }
    },
    dispose() {
      if (disposed) return
      disposed = true
      listeners.clear()
      document.off('update', updated)
      document.destroy()
    },
  }
}
