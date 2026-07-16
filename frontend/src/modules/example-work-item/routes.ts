import type { RouteRecordRaw } from 'vue-router'

export const exampleWorkItemRoutes: RouteRecordRaw[] = [
  {
    path: '/examples/work-items',
    name: 'example-work-item-list',
    component: () => import('./pages/WorkItemListPage.vue'),
    meta: {
      componentKey: 'example.work-item.list',
      permission: 'example.work-item.read',
      moduleKey: 'example.work-item',
    },
  },
  {
    path: '/examples/work-item-policies',
    name: 'example-work-item-policy',
    component: () => import('./pages/WorkItemPolicyPage.vue'),
    meta: {
      componentKey: 'example.work-item.policy',
      permission: 'example.work-item.policy-publish',
      moduleKey: 'example.work-item',
    },
  },
]
