import * as Y from 'yjs'
import { describe, expect, it, vi } from 'vitest'
import { createYjsCollaborationEngine } from '../src/engine'

describe('Yjs collaboration engine', () => {
  it('converges from updates and produces portable snapshots and state vectors', () => {
    const first = createYjsCollaborationEngine()
    const second = createYjsCollaborationEngine()
    const firstUpdates: Uint8Array[] = []; const secondUpdates: Uint8Array[] = []
    first.onUpdate((update, origin) => { if (origin === 'local') firstUpdates.push(update) })
    second.onUpdate((update, origin) => { if (origin === 'local') secondUpdates.push(update) })
    first.document.getText('content').insert(0, 'Peanut')
    second.document.getText('content').insert(0, 'Admin')
    for (const update of firstUpdates) second.applyUpdate(update)
    for (const update of secondUpdates) first.applyUpdate(update)
    expect(first.document.getText('content').toString()).toBe(second.document.getText('content').toString())
    expect(first.document.getText('content').toString()).toContain('Peanut')
    expect(first.document.getText('content').toString()).toContain('Admin')
    const restored = new Y.Doc()
    Y.applyUpdate(restored, first.encodeSnapshot())
    expect(restored.getText('content').toString()).toBe(first.document.getText('content').toString())
    expect(first.encodeStateVector().byteLength).toBeGreaterThan(0)
    restored.destroy(); first.dispose(); second.dispose()
  })

  it('labels replay and remote updates and disposes deterministically', () => {
    const source = new Y.Doc(); source.getMap('document').set('title', 'Draft')
    const engine = createYjsCollaborationEngine(); const listener = vi.fn()
    engine.onUpdate(listener); engine.applyUpdate(Y.encodeStateAsUpdate(source), 'replay')
    expect(listener).toHaveBeenCalledWith(expect.any(Uint8Array), 'replay')
    engine.dispose(); engine.dispose()
    expect(() => engine.encodeSnapshot()).toThrow('COLLABORATION_ENGINE_DISPOSED')
    source.destroy()
  })
})
