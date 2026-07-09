import { cn } from '@/lib/utils'

// uLam wordmark: lowercase u, uppercase L in the primary teal, lowercase am.
// Font: Baloo 2 ExtraBold, matching the brand spec used across the mobile app.
export function Wordmark({ className }: { className?: string }) {
  return (
    <span
      className={cn('font-baloo text-xl font-extrabold tracking-tight', className)}
    >
      u<span className='text-primary'>L</span>am
    </span>
  )
}
