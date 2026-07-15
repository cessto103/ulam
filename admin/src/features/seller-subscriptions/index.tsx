import { useState } from 'react'
import { AlertTriangle, CalendarClock, CreditCard, RefreshCw, Users } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import { useBillingSubscriptions, useBillingSummary, useBillingWebhooks } from './hooks/use-seller-subscriptions'

const statusClass: Record<string, string> = {
  active: 'bg-emerald-500/15 text-emerald-600',
  grace_period: 'bg-amber-500/15 text-amber-600',
  pending: 'bg-blue-500/15 text-blue-600',
  failed: 'bg-red-500/15 text-red-600',
  suspended: 'bg-red-500/15 text-red-600',
  expired: 'bg-muted text-muted-foreground',
  processed: 'bg-emerald-500/15 text-emerald-600',
  ignored: 'bg-muted text-muted-foreground',
}

export function SellerSubscriptions() {
  const [view, setView] = useState<'subscriptions' | 'webhooks'>('subscriptions')
  const [status, setStatus] = useState('all')
  const [page, setPage] = useState(1)
  const summary = useBillingSummary()
  const subscriptions = useBillingSubscriptions({ page, status: status === 'all' ? undefined : status })
  const webhooks = useBillingWebhooks({ page, status: status === 'all' ? undefined : status })
  const result = view === 'subscriptions' ? subscriptions : webhooks

  const selectView = (next: string) => {
    setView(next as 'subscriptions' | 'webhooks')
    setStatus('all')
    setPage(1)
  }

  return (
    <>
      <Header fixed><Search className='me-auto' /><ThemeSwitch /><ConfigDrawer /><ProfileDropdown /></Header>
      <Main className='flex flex-1 flex-col gap-4 sm:gap-6'>
        <div>
          <h2 className='text-2xl font-bold tracking-tight'>Subscription Billing</h2>
          <p className='text-muted-foreground'>PayMongo-backed subscriptions, revenue health, lifecycle state, and webhook processing.</p>
        </div>

        <div className='grid gap-3 sm:grid-cols-2 xl:grid-cols-4'>
          <Metric title='Active subscribers' value={summary.data?.active_subscribers ?? 0} icon={Users} />
          <Metric title='Revenue this month' value={`₱${Number(summary.data?.monthly_revenue ?? 0).toLocaleString()}`} icon={CreditCard} />
          <Metric title='Expiring in 7 days' value={summary.data?.expiring_soon ?? 0} icon={CalendarClock} />
          <Metric title='Webhook failures' value={summary.data?.webhook_failures ?? 0} icon={AlertTriangle} danger={(summary.data?.webhook_failures ?? 0) > 0} />
        </div>

        <div className='flex flex-wrap items-center justify-between gap-2'>
          <Tabs value={view} onValueChange={selectView}>
            <TabsList><TabsTrigger value='subscriptions'>Subscriptions</TabsTrigger><TabsTrigger value='webhooks'>Webhook log</TabsTrigger></TabsList>
          </Tabs>
          <div className='flex items-center gap-2'>
            <select className='h-9 rounded-md border bg-background px-3 text-sm' value={status} onChange={(e) => { setStatus(e.target.value); setPage(1) }}>
              <option value='all'>All statuses</option>
              {(view === 'subscriptions' ? ['active', 'grace_period', 'pending', 'suspended', 'expired', 'superseded'] : ['received', 'processed', 'failed', 'ignored']).map((item) => <option key={item} value={item}>{item.replace('_', ' ')}</option>)}
            </select>
            <Button variant='outline' size='icon' onClick={() => result.refetch()}><RefreshCw className='size-4' /></Button>
          </div>
        </div>

        <div className='overflow-hidden rounded-md border'>
          {view === 'subscriptions' ? (
            <Table><TableHeader><TableRow><TableHead>User</TableHead><TableHead>Plan</TableHead><TableHead>Provider</TableHead><TableHead>Status</TableHead><TableHead>Period end</TableHead><TableHead>Created</TableHead></TableRow></TableHeader>
              <TableBody>{subscriptions.isLoading ? <Empty text='Loading subscriptions…' span={6} /> : (subscriptions.data?.data ?? []).length === 0 ? <Empty text='No subscriptions found.' span={6} /> : subscriptions.data!.data.map((row) => (
                <TableRow key={row.id}><TableCell><div className='font-medium'>{row.user?.name ?? 'Deleted user'}</div><div className='text-xs text-muted-foreground'>{row.user?.email}</div></TableCell><TableCell><div className='font-medium'>{row.plan.name}</div><div className='text-xs text-muted-foreground'>{row.price?.duration ?? '-'} · {row.price ? `₱${Number(row.price.price).toFixed(2)}` : 'legacy'}</div></TableCell><TableCell className='capitalize'>{row.provider}</TableCell><TableCell><Badge className={`capitalize ${statusClass[row.status] ?? ''}`}>{row.status.replace('_', ' ')}</Badge>{row.cancel_at_period_end && <div className='mt-1 text-xs text-muted-foreground'>Cancels at period end</div>}</TableCell><TableCell>{row.current_period_end ? new Date(row.current_period_end).toLocaleString() : '-'}</TableCell><TableCell>{new Date(row.created_at).toLocaleString()}</TableCell></TableRow>
              ))}</TableBody></Table>
          ) : (
            <Table><TableHeader><TableRow><TableHead>Event ID</TableHead><TableHead>Type</TableHead><TableHead>Mode</TableHead><TableHead>Status</TableHead><TableHead>Received</TableHead><TableHead>Error</TableHead></TableRow></TableHeader>
              <TableBody>{webhooks.isLoading ? <Empty text='Loading webhook events…' span={6} /> : (webhooks.data?.data ?? []).length === 0 ? <Empty text='No webhook events found.' span={6} /> : webhooks.data!.data.map((row) => (
                <TableRow key={row.id}><TableCell className='font-mono text-xs'>{row.provider_event_id}</TableCell><TableCell>{row.event_type}</TableCell><TableCell>{row.livemode ? 'Live' : 'Test'}</TableCell><TableCell><Badge className={`capitalize ${statusClass[row.status] ?? ''}`}>{row.status}</Badge></TableCell><TableCell>{new Date(row.created_at).toLocaleString()}</TableCell><TableCell className='max-w-64 truncate text-xs text-red-600' title={row.error ?? ''}>{row.error ?? '-'}</TableCell></TableRow>
              ))}</TableBody></Table>
          )}
        </div>

        {(result.data?.last_page ?? 1) > 1 && <div className='flex justify-end gap-2'><Button variant='outline' size='sm' disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>Previous</Button><span className='py-2 text-sm text-muted-foreground'>Page {page} of {result.data?.last_page}</span><Button variant='outline' size='sm' disabled={page >= (result.data?.last_page ?? 1)} onClick={() => setPage((p) => p + 1)}>Next</Button></div>}
      </Main>
    </>
  )
}

function Metric({ title, value, icon: Icon, danger = false }: { title: string; value: string | number; icon: typeof Users; danger?: boolean }) {
  return <Card><CardHeader className='flex flex-row items-center justify-between pb-2'><CardTitle className='text-sm font-medium'>{title}</CardTitle><Icon className={`size-4 ${danger ? 'text-red-500' : 'text-muted-foreground'}`} /></CardHeader><CardContent><div className={`text-2xl font-bold ${danger ? 'text-red-600' : ''}`}>{value}</div></CardContent></Card>
}

function Empty({ text, span }: { text: string; span: number }) {
  return <TableRow><TableCell colSpan={span} className='h-24 text-center text-muted-foreground'>{text}</TableCell></TableRow>
}
