'use client'

import { AlertTriangle } from 'lucide-react'
import { toast } from 'sonner'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { type PostComment } from '../data/schema'
import { useDeleteComment } from '../hooks/use-comments'

type CommentDeleteDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: PostComment
}

export function CommentsDeleteDialog({
  open,
  onOpenChange,
  currentRow,
}: CommentDeleteDialogProps) {
  const { mutate: deleteComment, isPending } = useDeleteComment()

  const handleDelete = () => {
    deleteComment(currentRow.id, {
      onSuccess: () => {
        onOpenChange(false)
        toast.success(`Deleted comment #${currentRow.id}.`)
      },
      onError: (error: any) => {
        toast.error(
          error?.response?.data?.message ?? 'Could not delete comment.'
        )
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
          Delete Comment
        </span>
      }
      desc={
        <p>
          Are you sure you want to delete this comment by{' '}
          <span className='font-bold'>{currentRow.user.name}</span>?
          <br />
          This action cannot be undone.
        </p>
      }
      confirmText='Delete'
      destructive
    />
  )
}
