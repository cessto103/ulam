/// <reference types="vitest/config" />
import path from 'path'
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'
import { tanstackRouter } from '@tanstack/router-plugin/vite'
import { playwright } from '@vitest/browser-playwright'
import { version as appVersion } from './package.json'

// https://vite.dev/config/
export default defineConfig(({ command, mode }) => ({
  // Dev server stays at /. `npm run build` (mode=production) targets a real
  // deployment, where the domain's document root points straight at
  // Laravel's public/ folder, so the built app lives at /admin-panel.
  // `npm run build:wamp` (mode=wamp) targets local WAMP instead, where the
  // whole uLam folder sits under www/uLam/, so the built app lives at
  // /uLam/public/admin-panel — same shape as production, just local, so you
  // can test a real build before uploading it.
  base: command === 'build' ? (mode === 'wamp' ? '/uLam/public/admin-panel/' : '/admin-panel/') : '/',
  // Baked in at build time so the sidebar footer can show exactly which
  // build is actually being served -- `git pull` alone never updates this
  // (public/admin-panel is gitignored build output), so a stale-looking
  // number here is the fast way to tell "the deployed bundle wasn't
  // rebuilt after pulling" apart from "it was rebuilt, this really is
  // current." Only the version string is injected, not the whole
  // package.json (which would leak the full dependency list into the
  // client bundle).
  define: {
    __APP_VERSION__: JSON.stringify(appVersion),
  },
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
