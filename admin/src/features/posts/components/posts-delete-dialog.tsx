'use client'

import { AlertTriangle } from 'lucide-react'
import { toast } from 'sonner'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { type Post } from '../data/schema'
import { useDeletePost } from '../hooks/use-posts'

type PostDeleteDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: Post
  /** Fires only on a real successful delete -- unlike onOpenChange(false),
   * which also fires on a plain Cancel, so callers that need to react
   * specifically to deletion (e.g. navigating away from a detail page) can't
   * tell the two apart through onOpenChange alone. */
  onDeleted?: () => void
}

export function PostsDeleteDialog({
  open,
  onOpenChange,
  currentRow,
  onDeleted,
}: PostDeleteDialogProps) {
  const { mutate: deletePost, isPending } = useDeletePost()

  const handleDelete = () => {
    deletePost(currentRow.id, {
      onSuccess: () => {
        onOpenChange(false)
        onDeleted?.()
        toast.success(`Deleted post #${currentRow.id}.`)
      },
      onError: (error: any) => {
        toast.error(error?.response?.data?.message ?? 'Could not delete post.')
      },
    })
  }

  return (
    <ConfirmDialog
      open={open}
      onOpenChange={onOpenChange}
      handleConfirm={handleDelete}
      disabled={isPending}
      title={
        <span className='text-destructive'>
          <AlertTriangle
            className='me-1 inline-block stroke-destructive'
            size={18}
          />{' '}
          Delete Post
        </span>
      }
      desc={
        <p>
          Are you sure you want to delete post{' '}
          <span className='font-bold'>#{currentRow.id}</span> by{' '}
          <span className='font-bold'>{currentRow.user.name}</span>?
          <br />
          This action will permanently remove the post and its comments. This
          cannot be undone.
        </p>
      }
      confirmText='Delete'
      destructive
    />
  )
}
