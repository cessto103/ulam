import { useSidebar } from '@/components/ui/sidebar'

/** Shows exactly which build is being served -- baked in from package.json
 * at build time (see vite.config.ts). Since public/admin-panel is gitignored
 * build output, `git pull` never updates it by itself; this is the fast way
 * to tell "pulled but not rebuilt" apart from "genuinely current." */
export function AppVersion() {
  const { state, isMobile } = useSidebar()
  if (state === 'collapsed' && !isMobile) return null

  return (
    <div className='px-2 pb-1 text-center text-xs text-muted-foreground/70'>
      v{__APP_VERSION__}
    </div>
  )
}
