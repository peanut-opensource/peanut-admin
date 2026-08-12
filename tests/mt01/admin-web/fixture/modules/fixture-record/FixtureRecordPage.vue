<script setup lang="ts">
import {
  EmptyState,
  ForbiddenState,
  ModuleUnavailableState,
  NotFoundState,
  PageContent,
  PageHeader,
  PageToolbar,
  ServiceUnavailableState,
  SessionExpiredState,
} from '@peanut-admin/admin/shell'
import { inject, onBeforeUnmount, onMounted, ref } from 'vue'
import type { Component } from 'vue'

import { fixtureRecordTransportKey } from './index'

interface FixtureRecord {
  id: string
  name: string
}

interface ProblemView {
  detail: string
  kind: 'forbidden' | 'module-unavailable' | 'not-found' | 'service-unavailable' | 'session-expired'
  requestId: string | null
  title: string
}

const transport = inject(fixtureRecordTransportKey)
if (transport === undefined) throw new Error('MT01_FIXTURE_RECORD_TRANSPORT_MISSING')

const controller = ref<AbortController | null>(null)
const loading = ref(false)
const problem = ref<ProblemView | null>(null)
const records = ref<FixtureRecord[]>([])

const problemComponents: Readonly<Record<ProblemView['kind'], Component>> = {
  forbidden: ForbiddenState,
  'module-unavailable': ModuleUnavailableState,
  'not-found': NotFoundState,
  'service-unavailable': ServiceUnavailableState,
  'session-expired': SessionExpiredState,
}

const isObject = (value: unknown): value is Record<string, unknown> => (
  typeof value === 'object' && value !== null && !Array.isArray(value)
)

const parseRecords = (body: unknown): FixtureRecord[] => {
  if (!isObject(body) || !isObject(body.data) || !Array.isArray(body.data.items)) {
    throw new Error('MT01_FIXTURE_RECORD_RESPONSE_INVALID')
  }

  return body.data.items.map((item): FixtureRecord => {
    if (!isObject(item) || typeof item.id !== 'string' || typeof item.name !== 'string') {
      throw new Error('MT01_FIXTURE_RECORD_RESPONSE_INVALID')
    }
    return { id: item.id, name: item.name }
  })
}

const problemFor = (status: number, body: unknown, requestId: string | null): ProblemView => {
  const detail = isObject(body) && typeof body.detail === 'string'
    ? body.detail
    : 'The external Module request failed closed.'
  const code = isObject(body) && typeof body.code === 'string' ? body.code : null
  if (status === 401) return { detail, kind: 'session-expired', requestId, title: 'Session unavailable' }
  if (status === 403) return { detail, kind: 'forbidden', requestId, title: 'Access denied' }
  if (status === 404) return { detail, kind: 'not-found', requestId, title: 'Fixture records not found' }
  if (status === 409 || code === 'MODULE_UNAVAILABLE') {
    return { detail, kind: 'module-unavailable', requestId, title: 'Fixture Module unavailable' }
  }

  return { detail, kind: 'service-unavailable', requestId, title: 'Fixture records unavailable' }
}

const load = async () => {
  controller.value?.abort()
  const active = new AbortController()
  controller.value = active
  loading.value = true
  problem.value = null

  try {
    const response = await transport.list(active.signal)
    if (active.signal.aborted) return
    if (response.status !== 200) {
      records.value = []
      problem.value = problemFor(response.status, response.body, response.requestId)
      return
    }
    records.value = parseRecords(response.body)
  } catch (error) {
    if (active.signal.aborted) return
    records.value = []
    problem.value = {
      detail: error instanceof Error && error.message === 'MT01_FIXTURE_RECORD_RESPONSE_INVALID'
        ? 'The external Module returned an invalid response.'
        : 'The external Module request could not be completed.',
      kind: 'service-unavailable',
      requestId: null,
      title: 'Fixture records unavailable',
    }
  } finally {
    if (controller.value === active) {
      controller.value = null
      loading.value = false
    }
  }
}

onMounted(load)
onBeforeUnmount(() => controller.value?.abort())
</script>

<template>
  <PageContent>
    <PageHeader>Fixture Records</PageHeader>
    <PageToolbar label="Fixture record actions">
      <el-button
        :loading="loading"
        @click="load"
      >
        Refresh
      </el-button>
    </PageToolbar>
    <component
      :is="problemComponents[problem.kind]"
      v-if="problem"
      :title="problem.title"
      :message="problem.detail"
      :request-id="problem.requestId ?? undefined"
      @action="load"
    />
    <el-table
      v-else-if="records.length > 0"
      :data="records"
      class="fixture-record-table"
      data-testid="fixture-record-table"
    >
      <el-table-column
        prop="name"
        label="Name"
        min-width="240"
      />
      <el-table-column
        prop="id"
        label="Record ID"
        min-width="180"
      />
    </el-table>
    <EmptyState
      v-else-if="!loading"
      title="No fixture records"
      message="The selected Tenant has no fixture records."
    />
  </PageContent>
</template>
