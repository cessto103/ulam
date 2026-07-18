import { useState } from 'react'
import { AlertTriangle, Crown, Gift, Users } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import {
  usePremiumSubscribers,
  usePremiumSummary,
} from './hooks/use-premium-subscribers'

function daysUntil(dateStr: string | null): number | null {
  if (!dateStr) return null
  return Math.ceil((new Date(dateStr).getTime() - Date.now()) / 86_400_000)
}

export function PremiumSubscribers() {
  const [source, setSource] = useState<'all' | 'paid' | 'trial'>('all')
  const [expiringSoon, setExpiringSoon] = useState(false)
  const [page, setPage] = useState(1)

  const summary = usePremiumSummary()
  const list = usePremiumSubscribers({
    page,
    source: source === 'all' ? undefined : source,
    expiring_soon: expiringSoon,
  })

  const selectSource = (next: 'all' | 'paid' | 'trial') => {
    setSource(next)
    setPage(1)
  }

  return (
    <>
      <Header fixed><Search className='me-auto' /><ThemeSwitch /><ConfigDrawer /><ProfileDropdown /></Header>
      <Main className='flex flex-1 flex-col gap-4 sm:gap-6'>
        <div>
          <h2 className='text-2xl font-bold tracking-tight'>Premium Subscriptions</h2>
          <p className='text-muted-foreground'>
            Consumers currently on uLam Premium — separate from Seller Subscriptions, which is store-owner billing.
          </p>
        </div>

        <div className='grid gap-3 sm:grid-cols-2 xl:grid-cols-4'>
          <Metric title='Premium users' value={summary.data?.total ?? 0} icon={Crown} />
          <Metric title='Paid' value={summary.data?.paid ?? 0} icon={Users} />
          <Metric title='Trial' value={summary.data?.trial ?? 0} icon={Gift} />
          <Metric
            title='Expiring in 7 days'
            value={summary.data?.expiring_soon ?? 0}
            icon={AlertTriangle}
            danger={(summary.data?.expiring_soon ?? 0) > 0}
          />
        </div>

        <div className='flex flex-wrap items-center justify-between gap-2'>
          <div className='flex items-center gap-2'>
            <select
              className='h-9 rounded-md border bg-background px-3 text-sm'
              value={source}
              onChange={(e) => selectSource(e.target.value as 'all' | 'paid' | 'trial')}
            >
              <option value='all'>All sources</option>
              <option value='paid'>Paid</option>
              <option value='trial'>Trial</option>
            </select>
            <Button
              variant={expiringSoon ? 'default' : 'outline'}
              size='sm'
              onClick={() => { setExpiringSoon((v) => !v); setPage(1) }}
            >
              Expiring soon
            </Button>
          </div>
        </div>

        <div className='overflow-hidden rounded-md border'>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>User</TableHead>
                <TableHead>Source</TableHead>
                <TableHead>Expires</TableHead>
                <TableHead>Since</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {list.isLoading ? (
                <Empty text='Loading premium subscribers…' span={4} />
              ) : (list.data?.data ?? []).length === 0 ? (
                <Empty text='No premium subscribers found.' span={4} />
              ) : (
                list.data!.data.map((row) => {
                  const days = daysUntil(row.premium_expires_at)
                  return (
                    <TableRow key={row.id}>
                      <TableCell>
                        <div className='font-medium'>{row.name}</div>
                        <div className='text-xs text-muted-foreground'>{row.email}</div>
                      </TableCell>
                      <TableCell>
                        <Badge variant='outline' className='capitalize'>
                          {row.premium_source ?? 'unknown'}
                        </Badge>
                      </TableCell>
                      <TableCell>
                        {row.premium_expires_at ? (
                          <div>
                            <div>{new Date(row.premium_expires_at).toLocaleDateString()}</div>
                            {days !== null && (
                              <div className={`text-xs ${days <= 7 ? 'text-red-600' : 'text-muted-foreground'}`}>
                                {days < 0 ? 'Expired' : `${days} day${days === 1 ? '' : 's'} left`}
                              </div>
                            )}
                          </div>
                        ) : (
                          <span className='text-muted-foreground'>-</span>
                        )}
                      </TableCell>
                      <TableCell>{new Date(row.created_at).toLocaleDateString()}</TableCell>
                    </TableRow>
                  )
                })
              )}
            </TableBody>
          </Table>
        </div>

        {(list.data?.last_page ?? 1) > 1 && (
          <div className='flex justify-end gap-2'>
            <Button variant='outline' size='sm' disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>
              Previous
            </Button>
            <span className='py-2 text-sm text-muted-foreground'>
              Page {page} of {list.data?.last_page}
            </span>
            <Button
              variant='outline'
              size='sm'
              disabled={page >= (list.data?.last_page ?? 1)}
              onClick={() => setPage((p) => p + 1)}
            >
              Next
            </Button>
          </div>
        )}
      </Main>
    </>
  )
}

function Metric({
  title,
  value,
  icon: Icon,
  danger = false,
}: {
  title: string
  value: string | number
  icon: typeof Users
  danger?: boolean
}) {
  return (
    <Card>
      <CardHeader className='flex flex-row items-center justify-between pb-2'>
        <CardTitle className='text-sm font-medium'>{title}</CardTitle>
        <Icon className={`size-4 ${danger ? 'text-red-500' : 'text-muted-foreground'}`} />
      </CardHeader>
      <CardContent>
        <div className={`text-2xl font-bold ${danger ? 'text-red-600' : ''}`}>{value}</div>
      </CardContent>
    </Card>
  )
}

function Empty({ text, span }: { text: string; span: number }) {
  return (
    <TableRow>
      <TableCell colSpan={span} className='h-24 text-center text-muted-foreground'>
        {text}
      </TableCell>
    </TableRow>
  )
}
