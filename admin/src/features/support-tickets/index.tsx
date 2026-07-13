import { useState } from 'react'
import { toast } from 'sonner'
import { cn } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Textarea } from '@/components/ui/textarea'
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import {
  useCloseTicket,
  useReplyToTicket,
  useTicketQuery,
  useTicketsQuery,
} from './hooks/use-support-tickets'

const STATUS_BADGE: Record<string, string> = {
  open: 'bg-amber-500/15 text-amber-600 dark:text-amber-400',
  answered: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400',
  closed: 'bg-muted text-muted-foreground',
}

export function SupportTickets() {
  const [tab, setTab] = useState('open')
  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')
  const [openId, setOpenId] = useState<number | null>(null)
  const [reply, setReply] = useState('')

  const { data, isLoading } = useTicketsQuery({
    page,
    status: tab === 'all' ? undefined : tab,
    search,
  })
  const { data: ticket } = useTicketQuery(openId)
  const replyMutation = useReplyToTicket()
  const closeMutation = useCloseTicket()

  const rows = data?.data ?? []
  const openCount = data?.counts.open ?? 0

  return (
    <>
      <Header fixed>
        <Search className='me-auto' />
        <ThemeSwitch />
        <ConfigDrawer />
        <ProfileDropdown />
      </Header>

      <Main className='flex flex-1 flex-col gap-4 sm:gap-6'>
        <div>
          <h2 className='text-2xl font-bold tracking-tight'>Support Tickets</h2>
          <p className='text-muted-foreground'>
            Questions and payment issues from app users. Replying notifies the
            user in the app.
          </p>
        </div>

        <div className='flex flex-wrap items-center justify-between gap-2'>
          <Tabs
            value={tab}
            onValueChange={(v) => {
              setTab(v)
              setPage(1)
            }}
          >
            <TabsList>
              <TabsTrigger value='open'>
                Open
                {openCount > 0 && (
                  <Badge className='ms-1.5 bg-amber-500/15 text-amber-600 dark:text-amber-400'>
                    {openCount}
                  </Badge>
                )}
              </TabsTrigger>
              <TabsTrigger value='answered'>Answered</TabsTrigger>
              <TabsTrigger value='closed'>Closed</TabsTrigger>
              <TabsTrigger value='all'>All</TabsTrigger>
            </TabsList>
          </Tabs>
          <Input
            placeholder='Search subject or user...'
            className='h-9 w-64'
            value={search}
            onChange={(e) => {
              setSearch(e.target.value)
              setPage(1)
            }}
          />
        </div>

        <div className='flex flex-col gap-2'>
          {isLoading ? (
            <p className='py-12 text-center text-muted-foreground'>
              Loading...
            </p>
          ) : rows.length === 0 ? (
            <p className='py-12 text-center text-muted-foreground'>
              {tab === 'open' ? 'Inbox zero! 🎉' : 'No tickets found.'}
            </p>
          ) : (
            rows.map((t) => (
              <button
                key={t.id}
                type='button'
                onClick={() => {
                  setReply('')
                  setOpenId(t.id)
                }}
                className='flex items-start justify-between gap-3 rounded-md border p-3 text-start transition-colors hover:bg-muted'
              >
                <div className='min-w-0'>
                  <div className='flex flex-wrap items-center gap-2'>
                    <span className='font-medium'>{t.subject}</span>
                    <Badge variant='outline' className='capitalize'>
                      {t.category}
                    </Badge>
                    <Badge className={cn('capitalize', STATUS_BADGE[t.status])}>
                      {t.status}
                    </Badge>
                  </div>
                  <p className='mt-0.5 truncate text-sm text-muted-foreground'>
                    {t.latest_message?.is_from_admin ? 'You: ' : ''}
                    {t.latest_message?.body}
                  </p>
                  <p className='mt-0.5 text-xs text-muted-foreground'>
                    {t.user?.name} · {t.user?.email}
                  </p>
                </div>
                <span className='shrink-0 text-xs text-muted-foreground'>
                  {new Date(t.last_reply_at ?? t.created_at).toLocaleString()}
                </span>
              </button>
            ))
          )}
        </div>

        {(data?.last_page ?? 1) > 1 && (
          <div className='flex items-center justify-end gap-2'>
            <Button
              variant='outline'
              size='sm'
              disabled={page <= 1}
              onClick={() => setPage((p) => p - 1)}
            >
              Previous
            </Button>
            <span className='text-sm text-muted-foreground'>
              Page {data?.current_page} of {data?.last_page}
            </span>
            <Button
              variant='outline'
              size='sm'
              disabled={page >= (data?.last_page ?? 1)}
              onClick={() => setPage((p) => p + 1)}
            >
              Next
            </Button>
          </div>
        )}
      </Main>

      <Sheet open={openId !== null} onOpenChange={(o) => !o && setOpenId(null)}>
        <SheetContent className='flex w-full flex-col sm:max-w-lg'>
          <SheetHeader>
            <SheetTitle>{ticket?.subject}</SheetTitle>
            <SheetDescription>
              {ticket?.user?.name} · {ticket?.user?.email} ·{' '}
              <span className='capitalize'>{ticket?.category}</span>
            </SheetDescription>
          </SheetHeader>

          <ScrollArea className='min-h-0 flex-1 px-4'>
            <div className='flex flex-col gap-2 pb-4'>
              {(ticket?.messages ?? []).map((m) => (
                <div
                  key={m.id}
                  className={cn(
                    'max-w-[85%] rounded-lg px-3 py-2 text-sm',
                    m.is_from_admin
                      ? 'self-end bg-primary text-primary-foreground'
                      : 'self-start bg-muted'
                  )}
                >
                  <p className='whitespace-pre-wrap'>{m.body}</p>
                  <p
                    className={cn(
                      'mt-1 text-[10px]',
                      m.is_from_admin
                        ? 'text-primary-foreground/70'
                        : 'text-muted-foreground'
                    )}
                  >
                    {m.is_from_admin ? 'You' : (m.sender?.name ?? 'User')} ·{' '}
                    {new Date(m.created_at).toLocaleString()}
                  </p>
                </div>
              ))}
            </div>
          </ScrollArea>

          {ticket && ticket.status !== 'closed' && (
            <div className='space-y-2 border-t p-4'>
              <Textarea
                placeholder='Write a reply...'
                rows={3}
                value={reply}
                onChange={(e) => setReply(e.target.value)}
              />
              <div className='flex justify-between gap-2'>
                <Button
                  variant='outline'
                  size='sm'
                  disabled={closeMutation.isPending}
                  onClick={() =>
                    closeMutation.mutate(ticket.id, {
                      onSuccess: () => {
                        toast.success('Ticket closed.')
                        setOpenId(null)
                      },
                    })
                  }
                >
                  Close ticket
                </Button>
                <Button
                  size='sm'
                  disabled={!reply.trim() || replyMutation.isPending}
                  onClick={() =>
                    replyMutation.mutate(
                      { id: ticket.id, body: reply.trim() },
                      {
                        onSuccess: () => {
                          setReply('')
                          toast.success('Reply sent — user notified.')
                        },
                        onError: (error: any) =>
                          toast.error(
                            error?.response?.data?.message ??
                              'Could not send reply.'
                          ),
                      }
                    )
                  }
                >
                  Send reply
                </Button>
              </div>
            </div>
          )}
        </SheetContent>
      </Sheet>
    </>
  )
}
