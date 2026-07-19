// @vitest-environment happy-dom

import { flushPromises, mount } from '@vue/test-utils'
import {
  ElAlert,
  ElButton,
  ElDescriptions,
  ElDescriptionsItem,
  ElForm,
  ElFormItem,
  ElInput,
} from 'element-plus'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import AccountPage from '../src/pages/common/AccountPage.vue'

const mocks = vi.hoisted(() => ({
  context: { authorizationRevision: '17' },
  get: vi.fn(),
  logout: vi.fn(),
  patch: vi.fn(),
  post: vi.fn(),
  replace: vi.fn(),
  unwrap: vi.fn((result: { payload?: unknown }) => result.payload),
  workspace: {
    tenantIdentity: {
      accountLabel: 'Original account',
      actorLabel: 'Tenant member',
      contextLabel: 'Alpha tenant',
    },
  },
}))

vi.mock('@peanut-admin/admin-core', () => ({
  useTenantContext: () => mocks.context,
}))

vi.mock('../src/app/runtime', () => ({
  AdminApiError: class AdminApiError extends Error {
    public constructor(public readonly problem: unknown) {
      super('ADMIN_API_ERROR')
    }
  },
  useAdminRuntime: () => ({
    logout: mocks.logout,
    tenantClient: {
      GET: mocks.get,
      PATCH: mocks.patch,
      POST: mocks.post,
    },
    unwrap: mocks.unwrap,
  }),
}))

vi.mock('../src/app/store', () => ({
  useWorkspaceStore: () => mocks.workspace,
}))

vi.mock('vue-router', () => ({
  useRouter: () => ({ replace: mocks.replace }),
}))

const profile = (displayName = 'Ada Lovelace') => ({
  data: {
    account_id: '101',
    display_name: displayName,
    avatar_uri: 'https://cdn.example.test/avatars/101.png',
    credential: {
      kind: 'email_password',
      identifier_type: 'email',
      identifier_masked: 'a***@example.test',
      verified_at: '2026-07-18T10:00:00Z',
      secret_changed_at: '2026-07-17T09:00:00Z',
    },
  },
})

const mountPage = () => mount(AccountPage, {
  global: {
    components: {
      ElAlert,
      ElButton,
      ElDescriptions,
      ElDescriptionsItem,
      ElForm,
      ElFormItem,
      ElInput,
    },
  },
})

describe('AccountPage', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    mocks.get.mockResolvedValue({ payload: profile() })
    mocks.patch.mockResolvedValue({ payload: profile('Updated account') })
    mocks.post.mockResolvedValue({ payload: undefined })
    mocks.logout.mockResolvedValue(undefined)
    mocks.replace.mockResolvedValue(undefined)
    mocks.workspace.tenantIdentity.accountLabel = 'Original account'
  })

  it('loads the account profile and credential summary', async () => {
    const wrapper = mountPage()
    await flushPromises()

    expect(mocks.get).toHaveBeenCalledWith('/api/v1/account')
    expect(wrapper.get('input[data-testid="profile-display-name"]').element).toHaveProperty('value', 'Ada Lovelace')
    expect(wrapper.text()).toContain('a***@example.test')
    expect(wrapper.text()).toContain('17')
  })

  it('saves the editable profile and refreshes the account summary', async () => {
    const wrapper = mountPage()
    await flushPromises()

    await wrapper.get('input[data-testid="profile-display-name"]').setValue('  Updated account  ')
    await wrapper.get('input[data-testid="profile-avatar-uri"]').setValue('')
    await wrapper.get('[data-testid="profile-form"]').trigger('submit')
    await flushPromises()

    expect(mocks.patch).toHaveBeenCalledWith('/api/v1/account', {
      body: { display_name: 'Updated account', avatar_uri: null },
    })
    expect(mocks.workspace.tenantIdentity.accountLabel).toBe('Updated account')
    expect(wrapper.text()).toContain('个人资料已保存')
  })

  it('rejects a password confirmation mismatch without calling the API', async () => {
    const wrapper = mountPage()
    await flushPromises()

    await wrapper.get('input[data-testid="current-password"]').setValue('current-password')
    await wrapper.get('input[data-testid="new-password"]').setValue('a-secure-new-password')
    await wrapper.get('input[data-testid="confirm-password"]').setValue('a-different-password')
    await wrapper.get('[data-testid="password-form"]').trigger('submit')
    await flushPromises()

    expect(mocks.post).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('两次输入的新密码不一致')
  })

  it('clears tenant auth and redirects to login after changing the password', async () => {
    const wrapper = mountPage()
    await flushPromises()

    await wrapper.get('input[data-testid="current-password"]').setValue('current-password')
    await wrapper.get('input[data-testid="new-password"]').setValue('a-secure-new-password')
    await wrapper.get('input[data-testid="confirm-password"]').setValue('a-secure-new-password')
    await wrapper.get('[data-testid="password-form"]').trigger('submit')
    await flushPromises()

    expect(mocks.post).toHaveBeenCalledWith('/api/v1/account/password', {
      body: {
        current_password: 'current-password',
        new_password: 'a-secure-new-password',
      },
    })
    expect(mocks.logout).toHaveBeenCalledWith('tenant')
    expect(mocks.replace).toHaveBeenCalledWith('/login')
  })
})
