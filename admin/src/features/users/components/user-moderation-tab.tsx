import { AlertTriangle, Ban, ShieldAlert } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { useUserModerationQuery } from '../hooks/use-user-moderation'

const LEVEL_ICON = { 1: AlertTriangle, 2: ShieldAlert, 3: Ban } as const

function StatusBadge({ status }: { status: string }) {
  const destructive = ['open', 'pending'].includes(status)
  return (
    <Badge variant={destructive ? 'destructive' : 'outline'} className='capitalize'>
      {status.replace('_', ' ')}
    </Badge>
  )
}

export function UserModerationTab({ userId }: { userId: number }) {
  const { data, isLoading } = useUserModerationQuery(userId)

  if (isLoading || !data) {
    return <p className='text-sm text-muted-foreground'>Loading...</p>
  }

  const { strikes, content_reports_filed, content_reports_against, listing_reports_filed, support_tickets } = data

  return (
    <div className='space-y-4'>
      <Card>
        <CardHeader>
          <CardTitle className='text-sm'>Strike history ({strikes.length})</CardTitle>
        </CardHeader>
        <CardContent>
          {strikes.length === 0 ? (
            <p className='text-sm text-muted-foreground'>No strikes on record.</p>
          ) : (
            <div className='divide-y'>
              {strikes.map((strike) => {
                const Icon = LEVEL_ICON[strike.level as 1 | 2 | 3] ?? AlertTriangle
                const active = !strike.expires_at || new Date(strike.expires_at) > new Date()
                return (
                  <div key={strike.id} className='flex items-start gap-3 py-2.5 text-sm'>
                    <Icon size={16} className='mt-0.5 shrink-0 text-muted-foreground' />
                    <div className='flex-1'>
                      <div className='flex items-center gap-2'>
                        <span className='font-medium capitalize'>{strike.level_label}</span>
                        {active && <Badge variant='destructive'>Active</Badge>}
                      </div>
                      <p className='text-muted-foreground'>{strike.reason}</p>
                      <div className='mt-1 text-xs text-muted-foreground'>
                        {new Date(strike.created_at).toLocaleDateString()}
                        {strike.issuedBy && ` · by ${strike.issuedBy.name}`}
                        {strike.expires_at && ` · expires ${new Date(strike.expires_at).toLocaleDateString()}`}
                      </div>
                    </div>
                  </div>
                )
              })}
            </div>
          )}
        </CardContent>
      </Card>

      <div className='grid gap-4 lg:grid-cols-2'>
        <Card>
          <CardHeader>
            <CardTitle className='text-sm'>Reports against this user's content ({content_reports_against.length})</CardTitle>
          </CardHeader>
          <CardContent>
            {content_reports_against.length === 0 ? (
              <p className='text-sm text-muted-foreground'>No reports filed against this user's content.</p>
            ) : (
              <div className='divide-y'>
                {content_reports_against.map((r) => (
                  <div key={r.id} className='py-2.5 text-sm'>
                    <div className='flex items-center justify-between gap-2'>
                      <span className='font-medium capitalize'>{r.content_type} #{r.content_id}</span>
                      <StatusBadge status={r.status} />
                    </div>
                    <p className='text-xs text-muted-foreground'>{r.reason}</p>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className='text-sm'>Reports filed by this user ({content_reports_filed.length + listing_reports_filed.length})</CardTitle>
          </CardHeader>
          <CardContent>
            {content_reports_filed.length === 0 && listing_reports_filed.length === 0 ? (
              <p className='text-sm text-muted-foreground'>This user hasn't filed any reports.</p>
            ) : (
              <div className='divide-y'>
                {content_reports_filed.map((r) => (
                  <div key={`c-${r.id}`} className='py-2.5 text-sm'>
                    <div className='flex items-center justify-between gap-2'>
                      <span className='font-medium capitalize'>{r.content_type} #{r.content_id}</span>
                      <StatusBadge status={r.status} />
                    </div>
                    <p className='text-xs text-muted-foreground'>{r.reason}</p>
                  </div>
                ))}
                {listing_reports_filed.map((r) => (
                  <div key={`l-${r.id}`} className='py-2.5 text-sm'>
                    <div className='flex items-center justify-between gap-2'>
                      <span className='font-medium'>Listing #{r.reportable_id}</span>
                      <StatusBadge status={r.status} />
                    </div>
                    <p className='text-xs text-muted-foreground'>{r.reason}</p>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className='text-sm'>Support tickets ({support_tickets.length})</CardTitle>
        </CardHeader>
        <CardContent>
          {support_tickets.length === 0 ? (
            <p className='text-sm text-muted-foreground'>No support tickets.</p>
          ) : (
            <div className='divide-y'>
              {support_tickets.map((t) => (
                <div key={t.id} className='flex items-center justify-between gap-2 py-2.5 text-sm'>
                  <div>
                    <div className='font-medium'>{t.subject}</div>
                    <div className='text-xs text-muted-foreground capitalize'>
                      {t.category} · {new Date(t.created_at).toLocaleDateString()}
                    </div>
                  </div>
                  <StatusBadge status={t.status} />
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
