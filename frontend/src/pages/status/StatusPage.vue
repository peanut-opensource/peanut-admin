<script setup lang="ts">
import { EmptyState, ForbiddenState, ModuleUnavailableState } from '@peanut-admin/admin-shell'
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useWorkspaceStore } from '../../app/store'

const route = useRoute()
const router = useRouter()
const workspace = useWorkspaceStore()
const state = computed(() => String(route.name))
const requestId = computed(() => workspace.problem?.request_id ?? 'unavailable')
const message = computed(() => {
  if (workspace.problem !== null) return workspace.problem.detail
  if (String(route.query.code).startsWith('MODULE_')) return 'This module is currently unavailable.'
  return '当前请求无法完成。'
})

const retry = () => {
  workspace.problem = null
  if (window.history.length > 1) router.back()
  else void router.replace('/')
}
</script>

<template>
  <main class="standalone-state">
    <ForbiddenState
      v-if="state === 'state.forbidden'"
      :request-id="requestId"
    />
    <ModuleUnavailableState
      v-else-if="state === 'state.unavailable'"
      :message="message"
      :request-id="requestId"
      @action="retry"
    />
    <EmptyState
      v-else
      title="页面不存在"
      message="请求的页面不存在或已被移除。"
    />
    <el-button
      v-if="state !== 'state.unavailable'"
      @click="retry"
    >
      返回
    </el-button>
  </main>
</template>
