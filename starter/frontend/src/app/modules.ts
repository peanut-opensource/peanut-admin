import { exampleGreetingModule } from '../modules/example-greeting'
import { createPeanutSettingsHost } from '../modules/peanut-settings'
import type { PeanutSettingsHostOptions } from '../modules/peanut-settings'

export const createStarterModules = (options: PeanutSettingsHostOptions) => {
  const settings = createPeanutSettingsHost(options)

  return {
    modules: [exampleGreetingModule, settings.module] as const,
    settingsModule: settings.module,
    settingsRuntime: settings.runtime,
  }
}
