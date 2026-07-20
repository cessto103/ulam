import { Badge } from '@/components/ui/badge'
import { LEVEL_LABEL } from '../data/data'
import { type StrikeSummary } from '../data/schema'

/** Embedded inline in the Warn/Restrict/Ban confirm dialogs so an admin has
 * context on repeat offenders without a separate "user detail" page. */
export function ReportedUserStrikesSummary({
  userName,
  strikes,
  loading,
}: {
  userName: string
  strikes: StrikeSummary | undefined
  loading: boolean
}) {
  if (loading) {
    return <p className='text-xs text-muted-foreground'>Loading strike history…</p>
  }

  if (!strikes || strikes.active_count === 0) {
    return (
      <p className='text-xs text-muted-foreground'>
        {userName} has no active strikes.
      </p>
    )
  }

  return (
    <div className='rounded-md border p-2.5 text-xs'>
      <p className='mb-1.5 font-medium'>
        {userName} has {strikes.active_count} active{' '}
        {strikes.active_count === 1 ? 'strike' : 'strikes'}:
      </p>
      <div className='space-y-1'>
        {strikes.recent.map((s, i) => (
          <div key={i} className='flex items-center gap-1.5'>
            <Badge variant='outline' className='shrink-0'>
              {LEVEL_LABEL[s.level] ?? s.level}
            </Badge>
            <span className='truncate text-muted-foreground'>
              {s.reason} ({new Date(s.created_at).toLocaleDateString()})
            </span>
          </div>
        ))}
      </div>
    </div>
  )
}
