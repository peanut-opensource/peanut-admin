import { exampleGreetingModule } from '../modules/example-greeting'
import { createPeanutReferenceCodesHost } from '../modules/peanut-reference-codes'
import type { PeanutReferenceCodesHostOptions } from '../modules/peanut-reference-codes'
import { createPeanutSettingsHost } from '../modules/peanut-settings'
import type { PeanutSettingsHostOptions } from '../modules/peanut-settings'
import { createPeanutFileMediaHost } from '../modules/peanut-file-media'
import type { PeanutFileMediaHostOptions } from '../modules/peanut-file-media'

export type StarterModuleOptions = PeanutSettingsHostOptions & PeanutReferenceCodesHostOptions & PeanutFileMediaHostOptions

export const createStarterModules = (options: StarterModuleOptions) => {
  const settings = createPeanutSettingsHost(options)
  const referenceCodes = createPeanutReferenceCodesHost(options)
  const fileMedia = createPeanutFileMediaHost(options)

  return {
    modules: [exampleGreetingModule, settings.module, referenceCodes.module, fileMedia.module] as const,
    settingsModule: settings.module,
    settingsRuntime: settings.runtime,
    referenceCodesModule: referenceCodes.module,
    referenceCodesRuntime: referenceCodes.runtime,
    fileMediaModule: fileMedia.module,
    fileMediaRuntime: fileMedia.runtime,
  }
}
