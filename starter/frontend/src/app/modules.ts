import { exampleGreetingModule } from '../modules/example-greeting'
import { createPeanutReferenceCodesHost } from '../modules/peanut-reference-codes'
import type { PeanutReferenceCodesHostOptions } from '../modules/peanut-reference-codes'
import { createPeanutSettingsHost } from '../modules/peanut-settings'
import type { PeanutSettingsHostOptions } from '../modules/peanut-settings'
import { createPeanutFileMediaHost } from '../modules/peanut-file-media'
import type { PeanutFileMediaHostOptions } from '../modules/peanut-file-media'
import { createPeanutTaskJobHost } from '../modules/peanut-task-job'
import type { PeanutTaskJobHostOptions } from '../modules/peanut-task-job'
import { createPeanutNotificationSmsHost } from '../modules/peanut-notification-sms'
import type { PeanutNotificationSmsHostOptions } from '../modules/peanut-notification-sms'

export type StarterModuleOptions = PeanutSettingsHostOptions & PeanutReferenceCodesHostOptions & PeanutFileMediaHostOptions & PeanutTaskJobHostOptions & PeanutNotificationSmsHostOptions

export const createStarterModules = (options: StarterModuleOptions) => {
  const settings = createPeanutSettingsHost(options)
  const referenceCodes = createPeanutReferenceCodesHost(options)
  const fileMedia = createPeanutFileMediaHost(options)
  const taskJob = createPeanutTaskJobHost(options)
  const notificationSms = createPeanutNotificationSmsHost(options)

  return {
    modules: [exampleGreetingModule, settings.module, referenceCodes.module, fileMedia.module, taskJob.module, notificationSms.module] as const,
    settingsModule: settings.module,
    settingsRuntime: settings.runtime,
    referenceCodesModule: referenceCodes.module,
    referenceCodesRuntime: referenceCodes.runtime,
    fileMediaModule: fileMedia.module,
    fileMediaRuntime: fileMedia.runtime,
    taskJobModule: taskJob.module,
    taskJobRuntime: taskJob.runtime,
    notificationSmsModule: notificationSms.module,
    notificationSmsRuntime: notificationSms.runtime,
  }
}
