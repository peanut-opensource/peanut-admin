<script setup lang="ts">
import { PageContent, PageHeader } from '@peanut-admin/admin-shell'
import { useTenantContext } from '@peanut-admin/admin-core'

import { useWorkspaceStore } from '../../app/store'

const context = useTenantContext()
const workspace = useWorkspaceStore()
</script>

<template>
  <PageContent>
    <PageHeader>账号信息</PageHeader>
    <el-descriptions
      :column="1"
      border
      class="detail-list"
    >
      <el-descriptions-item label="账号">
        {{ workspace.tenantIdentity?.accountLabel }}
      </el-descriptions-item>
      <el-descriptions-item label="成员身份">
        {{ workspace.tenantIdentity?.actorLabel }}
      </el-descriptions-item>
      <el-descriptions-item label="租户">
        {{ workspace.tenantIdentity?.contextLabel }}
      </el-descriptions-item>
      <el-descriptions-item label="授权版本">
        {{ context.authorizationRevision }}
      </el-descriptions-item>
    </el-descriptions>
  </PageContent>
</template>
