import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'Peanut Admin',
  description: 'A reusable multi-tenant administration foundation for ThinkPHP and Vue.',
  lang: 'en-US',
  base: '/peanut-admin/',
  cleanUrls: true,
  lastUpdated: true,
  ignoreDeadLinks: false,
  head: [
    ['meta', { name: 'theme-color', content: '#176b4d' }],
  ],
  sitemap: {
    hostname: 'https://peanut-opensource.github.io/peanut-admin/',
  },
  themeConfig: {
    nav: [
      { text: 'Concepts', link: '/core-concepts/' },
      { text: 'Architecture', link: '/architecture/' },
      { text: 'Standards', link: '/standards/' },
      { text: 'API', link: '/api/' },
      { text: 'P0 Status', link: '/status/' },
    ],
    sidebar: [
      {
        text: 'Foundation',
        items: [
          { text: 'Overview', link: '/' },
          { text: 'Core Concepts', link: '/core-concepts/' },
          { text: 'Architecture', link: '/architecture/' },
        ],
      },
      {
        text: 'Development',
        items: [
          { text: 'Engineering Standards', link: '/standards/' },
          { text: 'Dependency Policy', link: '/standards/dependency-policy' },
          { text: 'Dependency Decisions', link: '/decisions/dependencies/' },
          { text: 'API Contract', link: '/api/' },
        ],
      },
      {
        text: 'Delivery',
        items: [
          { text: 'P0 Status', link: '/status/' },
        ],
      },
    ],
    search: {
      provider: 'local',
    },
    socialLinks: [
      { icon: 'github', link: 'https://github.com/peanut-opensource/peanut-admin' },
    ],
    editLink: {
      pattern: 'https://github.com/peanut-opensource/peanut-admin/edit/dev/docs/:path',
      text: 'Edit this page on GitHub',
    },
    footer: {
      message: 'Released under the Apache License 2.0.',
      copyright: 'Peanut Admin contributors',
    },
  },
})
