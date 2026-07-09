'use client'

import { toast } from 'sonner'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { type User } from '../data/schema'
import { useUnbanUser } from '../hooks/use-users'

type UsersUnbanDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: User
}

export function UsersUnbanDialog({
  open,
  onOpenChange,
  currentRow,
}: UsersUnbanDialogProps) {
  const { mutate: unbanUser, isPending } = useUnbanUser()

  const handleConfirm = () => {
    unbanUser(currentRow.id, {
      onSuccess: () => {
        onOpenChange(false)
        toast.success(`Unbanned ${currentRow.username}.`)
      },
      onError: (error: any) => {
        toast.error(error?.response?.data?.message ?? 'Could not unban user.')
      },
    })
  }

  return (
    <ConfirmDialog
      open={open}
      onOpenChange={onOpenChange}
      handleConfirm={handleConfirm}
      disabled={isPending}
      title='Unban user'
      confirmText='Unban'
      desc={
        <p>
          Restore <span className='font-bold'>{currentRow.username}</span>&apos;s
          access to the app?
        </p>
      }
    />
  )
}
