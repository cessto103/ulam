/// <reference types="vitest/config" />
import path from 'path'
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'
import { tanstackRouter } from '@tanstack/router-plugin/vite'
import { playwright } from '@vitest/browser-playwright'

// https://vite.dev/config/
export default defineConfig(({ command }) => ({
  // Dev server stays at /. Built app defaults to being served from a domain's
  // own /admin-panel (Hostinger and any real deployment, where the domain's
  // document root points straight at Laravel's public/ folder). For local
  // WAMP, where the whole uLam folder sits under www/uLam/, override with:
  //   VITE_ADMIN_BASE_PATH=/uLam/public/admin-panel/ npm run build
  base: command === 'build' ? (process.env.VITE_ADMIN_BASE_PATH || '/admin-panel/') : '/',
  build: {
    outDir: '../public/admin-panel',
    emptyOutDir: true,
  },
  plugins: [
    tanstackRouter({
      target: 'react',
      autoCodeSplitting: true,
    }),
    react(),
    tailwindcss(),
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  test: {
    silent: 'passed-only',
    unstubEnvs: true,
    browser: {
      enabled: true,
      provider: playwright(),
      instances: [{ browser: 'chromium' }],
    },
    coverage: {
      // include: ['src/**/*.{js,jsx,ts,tsx}'], // Uncomment to expand the report to all src/**/* so untested modules appear as 0% coverage.
      exclude: [
        'src/components/ui/**',
        'src/assets/**',
        'src/tanstack-table.d.ts',
        'src/routeTree.gen.ts',
        'src/test-utils/**',
        'src/routes/**',
      ],
    },
  },
}))
