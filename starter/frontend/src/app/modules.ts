import { exampleGreetingModule } from '../modules/example-greeting'
import { createPeanutReferenceCodesHost } from '../modules/peanut-reference-codes'
import type { PeanutReferenceCodesHostOptions } from '../modules/peanut-reference-codes'
import { createPeanutSettingsHost } from '../modules/peanut-settings'
import type { PeanutSettingsHostOptions } from '../modules/peanut-settings'

export type StarterModuleOptions = PeanutSettingsHostOptions & PeanutReferenceCodesHostOptions

export const createStarterModules = (options: StarterModuleOptions) => {
  const settings = createPeanutSettingsHost(options)
  const referenceCodes = createPeanutReferenceCodesHost(options)

  return {
    modules: [exampleGreetingModule, settings.module, referenceCodes.module] as const,
    settingsModule: settings.module,
    settingsRuntime: settings.runtime,
    referenceCodesModule: referenceCodes.module,
    referenceCodesRuntime: referenceCodes.runtime,
  }
}
