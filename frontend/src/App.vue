<script setup lang="ts">
import { ADMIN_CORE_PACKAGE } from '@peanut-admin/admin-core'
import { ADMIN_SHELL_PACKAGE } from '@peanut-admin/admin-shell'

const packageContract = `${ADMIN_CORE_PACKAGE}:${ADMIN_SHELL_PACKAGE}`
</script>

<template>
  <main
    class="development-host"
    :data-package-contract="packageContract"
  >
    <h1>Peanut Admin</h1>
  </main>
</template>
