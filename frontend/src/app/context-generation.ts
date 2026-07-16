export interface ContextGenerationTicket {
  value: number
  isCurrent: () => boolean
}

export interface ContextGeneration {
  current: () => number
  capture: () => ContextGenerationTicket
  advance: () => number
}

export const createContextGeneration = (): ContextGeneration => {
  let generation = 0

  return {
    current: () => generation,
    capture: () => {
      const value = generation
      return { value, isCurrent: () => value === generation }
    },
    advance: () => {
      generation += 1
      return generation
    },
  }
}
