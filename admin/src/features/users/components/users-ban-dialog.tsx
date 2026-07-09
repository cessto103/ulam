'use client'

import { useState } from 'react'
import { toast } from 'sonner'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { type User } from '../data/schema'
import { useBanUser } from '../hooks/use-users'

type UsersBanDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: User
}

export function UsersBanDialog({
  open,
  onOpenChange,
  currentRow,
}: UsersBanDialogProps) {
  const [reason, setReason] = useState('')
  const { mutate: banUser, isPending } = useBanUser()

  const handleConfirm = () => {
    if (!reason.trim()) return
    banUser(
      { id: currentRow.id, ban_reason: reason.trim() },
      {
        onSuccess: () => {
          setReason('')
          onOpenChange(false)
          toast.success(`Banned ${currentRow.username}.`)
        },
        onError: (error: any) => {
          toast.error(error?.response?.data?.message ?? 'Could not ban user.')
        },
      }
    )
  }

  return (
    <ConfirmDialog
      open={open}
      onOpenChange={onOpenChange}
      handleConfirm={handleConfirm}
      disabled={!reason.trim() || isPending}
      destructive
      title='Ban user'
      confirmText='Ban'
      desc={
        <div className='space-y-3'>
          <p>
            This will suspend <span className='font-bold'>{currentRow.username}</span>{' '}
            from the app entirely.
          </p>
          <Label className='flex flex-col items-start gap-1.5'>
            <span>Reason</span>
            <Textarea
              value={reason}
              onChange={(e) => setReason(e.target.value)}
              placeholder='Why is this user being banned?'
              autoFocus
            />
          </Label>
        </div>
      }
    />
  )
}
