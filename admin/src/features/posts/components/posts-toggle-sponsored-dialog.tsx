'use client'

import { toast } from 'sonner'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { type Post } from '../data/schema'
import { useToggleSponsored } from '../hooks/use-posts'

type PostsToggleSponsoredDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: Post
}

export function PostsToggleSponsoredDialog({
  open,
  onOpenChange,
  currentRow,
}: PostsToggleSponsoredDialogProps) {
  const { mutate: toggleSponsored, isPending } = useToggleSponsored()
  const nextValue = !currentRow.is_sponsored

  const handleConfirm = () => {
    toggleSponsored(
      { id: currentRow.id, is_sponsored: nextValue },
      {
        onSuccess: () => {
          onOpenChange(false)
          toast.success(
            nextValue
              ? 'Post marked as sponsored.'
              : 'Post sponsorship removed.'
          )
        },
        onError: (error: any) => {
          toast.error(
            error?.response?.data?.message ?? 'Could not update post.'
          )
        },
      }
    )
  }

  return (
    <ConfirmDialog
      open={open}
      onOpenChange={onOpenChange}
      handleConfirm={handleConfirm}
      disabled={isPending}
      title={nextValue ? 'Mark post as sponsored' : 'Remove sponsorship'}
      confirmText={nextValue ? 'Mark sponsored' : 'Remove'}
      desc={
        <p>
          {nextValue ? (
            <>
              Mark post <span className='font-bold'>#{currentRow.id}</span> by{' '}
              <span className='font-bold'>{currentRow.user.name}</span> as
              sponsored content?
            </>
          ) : (
            <>
              Remove the sponsored label from post{' '}
              <span className='font-bold'>#{currentRow.id}</span> by{' '}
              <span className='font-bold'>{currentRow.user.name}</span>?
            </>
          )}
        </p>
      }
    />
  )
}
