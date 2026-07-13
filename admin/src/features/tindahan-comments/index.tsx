import { useState } from 'react'
import { Star, Trash2 } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import {
  useDeleteTindahanComment,
  useDeleteTindahanRating,
  useTindahanComments,
  useTindahanRatings,
  type TindahanCommentRow,
  type TindahanRatingRow,
} from './hooks/use-tindahan-comments'

export function TindahanComments() {
  const [view, setView] = useState<'comments' | 'ratings'>('comments')
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  const [deletingComment, setDeletingComment] = useState<TindahanCommentRow | null>(null)
  const [deletingRating, setDeletingRating] = useState<TindahanRatingRow | null>(null)

  const comments = useTindahanComments({ page, search: search || undefined })
  const ratings = useTindahanRatings({ page })
  const deleteComment = useDeleteTindahanComment()
  const deleteRating = useDeleteTindahanRating()

  const result = view === 'comments' ? comments : ratings

  const selectView = (next: string) => {
    setView(next as 'comments' | 'ratings')
    setSearch('')
    setPage(1)
  }

  return (
    <>
      <Header fixed><Search className='me-auto' /><ThemeSwitch /><ConfigDrawer /><ProfileDropdown /></Header>
      <Main className='flex flex-1 flex-col gap-4 sm:gap-6'>
        <div>
          <h2 className='text-2xl font-bold tracking-tight'>Store Comments & Ratings</h2>
          <p className='text-muted-foreground'>Moderate comments and ratings left on stores.</p>
        </div>

        <div className='flex flex-wrap items-center justify-between gap-2'>
          <Tabs value={view} onValueChange={selectView}>
            <TabsList>
              <TabsTrigger value='comments'>Comments</TabsTrigger>
              <TabsTrigger value='ratings'>Ratings</TabsTrigger>
            </TabsList>
          </Tabs>
          {view === 'comments' && (
            <Input
              placeholder='Search comment text…'
              className='h-9 w-64'
              value={search}
              onChange={(e) => { setSearch(e.target.value); setPage(1) }}
            />
          )}
        </div>

        <div className='overflow-hidden rounded-md border'>
          {view === 'comments' ? (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>User</TableHead>
                  <TableHead>Store</TableHead>
                  <TableHead>Comment</TableHead>
                  <TableHead>Posted</TableHead>
                  <TableHead className='text-right'>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {comments.isLoading ? (
                  <Empty text='Loading comments…' span={5} />
                ) : (comments.data?.data ?? []).length === 0 ? (
                  <Empty text='No comments found.' span={5} />
                ) : (
                  comments.data!.data.map((row) => (
                    <TableRow key={row.id}>
                      <TableCell>
                        <div className='font-medium'>{row.user?.name ?? 'Deleted user'}</div>
                        <div className='text-xs text-muted-foreground'>@{row.user?.username}</div>
                      </TableCell>
                      <TableCell>{row.tindahan?.name ?? '—'}</TableCell>
                      <TableCell className='max-w-96'>{row.body}</TableCell>
                      <TableCell>{new Date(row.created_at).toLocaleString()}</TableCell>
                      <TableCell className='text-right'>
                        <Button variant='ghost' size='icon' onClick={() => setDeletingComment(row)}>
                          <Trash2 className='size-4 text-red-500' />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>User</TableHead>
                  <TableHead>Store</TableHead>
                  <TableHead>Rating</TableHead>
                  <TableHead>Given</TableHead>
                  <TableHead className='text-right'>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {ratings.isLoading ? (
                  <Empty text='Loading ratings…' span={5} />
                ) : (ratings.data?.data ?? []).length === 0 ? (
                  <Empty text='No ratings found.' span={5} />
                ) : (
                  ratings.data!.data.map((row) => (
                    <TableRow key={row.id}>
                      <TableCell>
                        <div className='font-medium'>{row.user?.name ?? 'Deleted user'}</div>
                        <div className='text-xs text-muted-foreground'>@{row.user?.username}</div>
                      </TableCell>
                      <TableCell>{row.tindahan?.name ?? '—'}</TableCell>
                      <TableCell>
                        <div className='flex items-center gap-1'>
                          {Array.from({ length: 5 }).map((_, i) => (
                            <Star key={i} className={`size-3.5 ${i < row.rating ? 'fill-amber-400 text-amber-400' : 'text-muted-foreground'}`} />
                          ))}
                        </div>
                      </TableCell>
                      <TableCell>{new Date(row.created_at).toLocaleString()}</TableCell>
                      <TableCell className='text-right'>
                        <Button variant='ghost' size='icon' onClick={() => setDeletingRating(row)}>
                          <Trash2 className='size-4 text-red-500' />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          )}
        </div>

        {(result.data?.last_page ?? 1) > 1 && (
          <div className='flex justify-end gap-2'>
            <Button variant='outline' size='sm' disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>Previous</Button>
            <span className='py-2 text-sm text-muted-foreground'>Page {page} of {result.data?.last_page}</span>
            <Button variant='outline' size='sm' disabled={page >= (result.data?.last_page ?? 1)} onClick={() => setPage((p) => p + 1)}>Next</Button>
          </div>
        )}
      </Main>

      <AlertDialog open={!!deletingComment} onOpenChange={(open) => !open && setDeletingComment(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete this comment?</AlertDialogTitle>
            <AlertDialogDescription>
              "{deletingComment?.body}" — this cannot be undone.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={() => {
                if (!deletingComment) return
                deleteComment.mutate(deletingComment.id, {
                  onSuccess: () => { toast.success('Comment deleted.'); setDeletingComment(null) },
                  onError: (error: any) => toast.error(error?.response?.data?.message ?? 'Could not delete.'),
                })
              }}
            >
              Delete
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      <AlertDialog open={!!deletingRating} onOpenChange={(open) => !open && setDeletingRating(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Remove this rating?</AlertDialogTitle>
            <AlertDialogDescription>
              The store's average rating will be recalculated after removal. This cannot be undone.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={() => {
                if (!deletingRating) return
                deleteRating.mutate(deletingRating.id, {
                  onSuccess: () => { toast.success('Rating removed.'); setDeletingRating(null) },
                  onError: (error: any) => toast.error(error?.response?.data?.message ?? 'Could not delete.'),
                })
              }}
            >
              Delete
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </>
  )
}

function Empty({ text, span }: { text: string; span: number }) {
  return <TableRow><TableCell colSpan={span} className='h-24 text-center text-muted-foreground'>{text}</TableCell></TableRow>
}
