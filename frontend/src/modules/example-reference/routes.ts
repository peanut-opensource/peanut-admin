import type { RouteRecordRaw } from 'vue-router'

export const exampleReferenceRoutes: RouteRecordRaw[] = [
  {
    path: '/examples/references',
    name: 'example-reference-list',
    component: () => import('./pages/ReferenceListPage.vue'),
    meta: {
      componentKey: 'example.reference.list',
      permission: 'example.reference.read',
      moduleKey: 'example.reference',
    },
  },
]
