import type { RouteRecordRaw } from 'vue-router'

export const exampleTargetRoutes: RouteRecordRaw[] = [
  {
    path: '/examples/targets',
    name: 'example-target-list',
    component: () => import('./pages/TargetListPage.vue'),
    meta: {
      componentKey: 'example.target.list',
      permission: 'example.target.read',
      moduleKey: 'example.target',
    },
  },
]
