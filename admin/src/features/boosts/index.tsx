import { useState } from 'react'
import { CheckCircle2, Rocket, XCircle } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import { toast } from 'sonner'
import { RejectBoostDialog } from './components/reject-boost-dialog'
import { useApproveBoost, useBoosts, type Boost } from './hooks/use-boosts'

const statusClass: Record<string, string> = {
  active: 'bg-emerald-500/15 text-emerald-600',
  pending: 'bg-blue-500/15 text-blue-600',
  expired: 'bg-muted text-muted-foreground',
  rejected: 'bg-red-500/15 text-red-600',
}

export function Boosts() {
  const [status, setStatus] = useState('all')
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  const [rejecting, setRejecting] = useState<Boost | null>(null)

  const { data, isLoading } = useBoosts({
    page,
    status: status === 'all' ? undefined : status,
    search: search || undefined,
  })
  const approve = useApproveBoost()

  const selectStatus = (next: string) => {
    setStatus(next)
    setPage(1)
  }

  return (
    <>
      <Header fixed><Search className='me-auto' /><ThemeSwitch /><ConfigDrawer /><ProfileDropdown /></Header>
      <Main className='flex flex-1 flex-col gap-4 sm:gap-6'>
        <div>
          <h2 className='text-2xl font-bold tracking-tight'>Boost Review</h2>
          <p className='text-muted-foreground'>Manual GCash payments for recipe and store boosts, awaiting verification.</p>
        </div>

        <div className='grid gap-3 sm:grid-cols-2'>
          <Card>
            <CardHeader className='flex flex-row items-center justify-between pb-2'>
              <CardTitle className='text-sm font-medium'>Pending review</CardTitle>
              <Rocket className='size-4 text-muted-foreground' />
            </CardHeader>
            <CardContent><div className='text-2xl font-bold'>{data?.counts.pending ?? 0}</div></CardContent>
          </Card>
          <Card>
            <CardHeader className='flex flex-row items-center justify-between pb-2'>
              <CardTitle className='text-sm font-medium'>Currently active</CardTitle>
              <CheckCircle2 className='size-4 text-muted-foreground' />
            </CardHeader>
            <CardContent><div className='text-2xl font-bold'>{data?.counts.active ?? 0}</div></CardContent>
          </Card>
        </div>

        <div className='flex flex-wrap items-center justify-between gap-2'>
          <div className='flex items-center gap-2'>
            <select
              className='h-9 rounded-md border bg-background px-3 text-sm'
              value={status}
              onChange={(e) => selectStatus(e.target.value)}
            >
              <option value='all'>All statuses</option>
              {['pending', 'active', 'expired', 'rejected'].map((s) => (
                <option key={s} value={s}>{s}</option>
              ))}
            </select>
            <Input
              placeholder='Search reference, name, email…'
              className='h-9 w-64'
              value={search}
              onChange={(e) => { setSearch(e.target.value); setPage(1) }}
            />
          </div>
        </div>

        <div className='overflow-hidden rounded-md border'>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Seller</TableHead>
                <TableHead>Boosted item</TableHead>
                <TableHead>Duration</TableHead>
                <TableHead>Amount</TableHead>
                <TableHead>Reference</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Submitted</TableHead>
                <TableHead className='text-right'>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {isLoading ? (
                <Empty text='Loading boosts…' />
              ) : (data?.data ?? []).length === 0 ? (
                <Empty text='No boost submissions found.' />
              ) : (
                data!.data.map((row) => (
                  <TableRow key={row.id}>
                    <TableCell>
                      <div className='font-medium'>{row.user?.name ?? 'Deleted user'}</div>
                      <div className='text-xs text-muted-foreground'>{row.user?.email}</div>
                    </TableCell>
                    <TableCell>
                      <span className='capitalize text-xs text-muted-foreground'>{row.target === 'recipe' ? '🍽️ Recipe' : '🏪 Store'}</span>
                      <div className='font-medium'>{row.target_name ?? '—'}</div>
                    </TableCell>
                    <TableCell>{row.duration_days ? `${row.duration_days} days` : '—'}</TableCell>
                    <TableCell>₱{Number(row.amount_paid).toFixed(2)}</TableCell>
                    <TableCell className='font-mono text-xs'>{row.payment_reference ?? '—'}</TableCell>
                    <TableCell>
                      <Badge className={`capitalize ${statusClass[row.status] ?? ''}`}>{row.status}</Badge>
                      {row.status === 'rejected' && row.rejected_reason && (
                        <div className='mt-1 max-w-48 truncate text-xs text-muted-foreground' title={row.rejected_reason}>{row.rejected_reason}</div>
                      )}
                    </TableCell>
                    <TableCell>{new Date(row.created_at).toLocaleString()}</TableCell>
                    <TableCell className='text-right'>
                      {row.status === 'pending' && (
                        <div className='flex justify-end gap-2'>
                          <Button
                            size='sm'
                            disabled={approve.isPending}
                            onClick={() =>
                              approve.mutate(row.id, {
                                onSuccess: () => toast.success('Boost activated.'),
                                onError: (error: any) =>
                                  toast.error(error?.response?.data?.message ?? 'Could not approve.'),
                              })
                            }
                          >
                            <CheckCircle2 className='size-4' /> Approve
                          </Button>
                          <Button size='sm' variant='outline' onClick={() => setRejecting(row)}>
                            <XCircle className='size-4' /> Reject
                          </Button>
                        </div>
                      )}
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </div>

        {(data?.last_page ?? 1) > 1 && (
          <div className='flex justify-end gap-2'>
            <Button variant='outline' size='sm' disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>Previous</Button>
            <span className='py-2 text-sm text-muted-foreground'>Page {page} of {data?.last_page}</span>
            <Button variant='outline' size='sm' disabled={page >= (data?.last_page ?? 1)} onClick={() => setPage((p) => p + 1)}>Next</Button>
          </div>
        )}
      </Main>

      <RejectBoostDialog boost={rejecting} onOpenChange={(open) => { if (!open) setRejecting(null); }} />
    </>
  )
}

function Empty({ text }: { text: string }) {
  return <TableRow><TableCell colSpan={8} className='h-24 text-center text-muted-foreground'>{text}</TableCell></TableRow>
}
