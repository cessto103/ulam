import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { useUserMonetizationQuery } from '../hooks/use-user-monetization'

function money(amount: number, currency = 'PHP') {
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency }).format(amount / 100)
}

function StatusBadge({ status }: { status: string }) {
  const destructive = ['failed', 'cancelled', 'rejected', 'suspended', 'expired'].includes(status)
  const positive = ['active', 'paid', 'approved', 'succeeded'].includes(status)
  return (
    <Badge variant={destructive ? 'destructive' : positive ? 'default' : 'outline'} className='capitalize'>
      {status.replace('_', ' ')}
    </Badge>
  )
}

export function UserMonetizationTab({ userId }: { userId: number }) {
  const { data, isLoading } = useUserMonetizationQuery(userId)

  if (isLoading || !data) {
    return <p className='text-sm text-muted-foreground'>Loading...</p>
  }

  const { premium, subscriptions, seller_subscriptions, payments, refunds } = data

  return (
    <div className='space-y-4'>
      <Card>
        <CardHeader>
          <CardTitle className='text-sm'>Premium status</CardTitle>
        </CardHeader>
        <CardContent className='flex flex-wrap items-center gap-3 text-sm'>
          <Badge variant={premium.is_premium ? 'default' : 'outline'} className='capitalize'>
            {premium.plan}
          </Badge>
          {premium.premium_source && (
            <span className='text-muted-foreground capitalize'>via {premium.premium_source}</span>
          )}
          {premium.premium_expires_at && (
            <span className='text-muted-foreground'>
              {premium.is_premium ? 'Expires' : 'Expired'} {new Date(premium.premium_expires_at).toLocaleDateString()}
            </span>
          )}
        </CardContent>
      </Card>

      <div className='grid gap-4 lg:grid-cols-2'>
        <Card>
          <CardHeader>
            <CardTitle className='text-sm'>Seller subscriptions ({seller_subscriptions.length})</CardTitle>
          </CardHeader>
          <CardContent>
            {seller_subscriptions.length === 0 ? (
              <p className='text-sm text-muted-foreground'>No seller subscriptions.</p>
            ) : (
              <div className='divide-y'>
                {seller_subscriptions.map((s) => (
                  <div key={s.id} className='py-2.5 text-sm'>
                    <div className='flex items-center justify-between gap-2'>
                      <span className='font-medium capitalize'>{s.plan} · {s.duration}</span>
                      <StatusBadge status={s.status} />
                    </div>
                    <div className='mt-1 flex flex-wrap items-center gap-2 text-xs text-muted-foreground'>
                      {s.tindahan && <span>{s.tindahan.name}</span>}
                      <span>₱{s.amount_paid}</span>
                      {s.expires_at && <span>expires {new Date(s.expires_at).toLocaleDateString()}</span>}
                      {s.refunded_at && <span className='text-destructive'>refunded</span>}
                    </div>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className='text-sm'>Premium subscriptions ({subscriptions.length})</CardTitle>
          </CardHeader>
          <CardContent>
            {subscriptions.length === 0 ? (
              <p className='text-sm text-muted-foreground'>No premium subscriptions.</p>
            ) : (
              <div className='divide-y'>
                {subscriptions.map((s) => (
                  <div key={s.id} className='py-2.5 text-sm'>
                    <div className='flex items-center justify-between gap-2'>
                      <span className='font-medium'>{s.plan?.name ?? 'Premium'} · {s.price?.duration ?? '-'}</span>
                      <StatusBadge status={s.status} />
                    </div>
                    <div className='mt-1 flex flex-wrap items-center gap-2 text-xs text-muted-foreground'>
                      {s.current_period_end && (
                        <span>renews/ends {new Date(s.current_period_end).toLocaleDateString()}</span>
                      )}
                      {s.cancel_at_period_end && <span className='text-destructive'>cancels at period end</span>}
                    </div>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className='text-sm'>Payments ({payments.length})</CardTitle>
        </CardHeader>
        <CardContent>
          {payments.length === 0 ? (
            <p className='text-sm text-muted-foreground'>No payments yet.</p>
          ) : (
            <div className='divide-y'>
              {payments.map((p) => (
                <div key={p.id} className='flex items-center justify-between gap-2 py-2.5 text-sm'>
                  <div>
                    <div className='font-medium capitalize'>{p.plan_type ?? p.provider}</div>
                    <div className='text-xs text-muted-foreground'>
                      {new Date(p.created_at).toLocaleString()}
                      {p.failure_message && ` · ${p.failure_message}`}
                    </div>
                  </div>
                  <div className='flex items-center gap-2'>
                    <span className='font-medium'>{money(p.amount, p.currency)}</span>
                    <StatusBadge status={p.status} />
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className='text-sm'>Refunds ({refunds.length})</CardTitle>
        </CardHeader>
        <CardContent>
          {refunds.length === 0 ? (
            <p className='text-sm text-muted-foreground'>No refunds.</p>
          ) : (
            <div className='divide-y'>
              {refunds.map((r) => (
                <div key={r.id} className='flex items-center justify-between gap-2 py-2.5 text-sm'>
                  <div>
                    <div className='font-medium'>{r.reason ?? 'Refund'}</div>
                    <div className='text-xs text-muted-foreground'>
                      {r.processed_at ? new Date(r.processed_at).toLocaleString() : new Date(r.created_at).toLocaleString()}
                    </div>
                  </div>
                  <div className='flex items-center gap-2'>
                    <span className='font-medium'>{money(r.amount, r.currency)}</span>
                    <StatusBadge status={r.status} />
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
