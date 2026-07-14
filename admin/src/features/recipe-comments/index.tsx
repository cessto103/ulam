import { useState } from 'react'
import { Trash2 } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
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
  useDeleteRecipeComment,
  useRecipeComments,
  type RecipeCommentRow,
} from './hooks/use-recipe-comments'

export function RecipeComments() {
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  const [deleting, setDeleting] = useState<RecipeCommentRow | null>(null)

  const comments = useRecipeComments({ page, search: search || undefined })
  const deleteComment = useDeleteRecipeComment()

  return (
    <>
      <Header fixed><Search className='me-auto' /><ThemeSwitch /><ConfigDrawer /><ProfileDropdown /></Header>
      <Main className='flex flex-1 flex-col gap-4 sm:gap-6'>
        <div>
          <h2 className='text-2xl font-bold tracking-tight'>Recipe Comments</h2>
          <p className='text-muted-foreground'>Moderate comments left on recipes.</p>
        </div>

        <Input
          placeholder='Search comment text…'
          className='h-9 w-64'
          value={search}
          onChange={(e) => { setSearch(e.target.value); setPage(1) }}
        />

        <div className='overflow-hidden rounded-md border'>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>User</TableHead>
                <TableHead>Recipe</TableHead>
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
                    <TableCell>{row.recipe?.title ?? '—'}</TableCell>
                    <TableCell className='max-w-96'>{row.body}</TableCell>
                    <TableCell>{new Date(row.created_at).toLocaleString()}</TableCell>
                    <TableCell className='text-right'>
                      <Button variant='ghost' size='icon' onClick={() => setDeleting(row)}>
                        <Trash2 className='size-4 text-red-500' />
                      </Button>
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </div>

        {(comments.data?.last_page ?? 1) > 1 && (
          <div className='flex justify-end gap-2'>
            <Button variant='outline' size='sm' disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>Previous</Button>
            <span className='py-2 text-sm text-muted-foreground'>Page {page} of {comments.data?.last_page}</span>
            <Button variant='outline' size='sm' disabled={page >= (comments.data?.last_page ?? 1)} onClick={() => setPage((p) => p + 1)}>Next</Button>
          </div>
        )}
      </Main>

      <AlertDialog open={!!deleting} onOpenChange={(open) => !open && setDeleting(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete this comment?</AlertDialogTitle>
            <AlertDialogDescription>
              "{deleting?.body}" — this cannot be undone.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={() => {
                if (!deleting) return
                deleteComment.mutate(deleting.id, {
                  onSuccess: () => { toast.success('Comment deleted.'); setDeleting(null) },
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
