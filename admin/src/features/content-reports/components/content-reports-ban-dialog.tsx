'use client'

import { useState } from 'react'
import { toast } from 'sonner'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { type ContentReport } from '../data/schema'
import { useBanReportedUser, useContentReport } from '../hooks/use-content-reports'
import { ReportedUserStrikesSummary } from './reported-user-strikes-summary'

type ContentReportsBanDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: ContentReport
}

export function ContentReportsBanDialog({
  open,
  onOpenChange,
  currentRow,
}: ContentReportsBanDialogProps) {
  const [reason, setReason] = useState(
    currentRow.reason + (currentRow.details ? `: ${currentRow.details}` : '')
  )
  const { data: detail, isLoading: loadingDetail } = useContentReport(open ? currentRow.id : null)
  const { mutate: ban, isPending } = useBanReportedUser()

  const userName = currentRow.reported_user?.name ?? 'this user'

  const handleConfirm = () => {
    if (!reason.trim()) return
    ban(
      { id: currentRow.id, reason: reason.trim() },
      {
        onSuccess: () => {
          onOpenChange(false)
          toast.success(`Banned ${userName}.`)
        },
        onError: (error: any) =>
          toast.error(error?.response?.data?.message ?? 'Could not ban this user.'),
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
      confirmText='Ban permanently'
      desc={
        <div className='space-y-3'>
          <p>
            This permanently suspends <b>{userName}</b> from the app,
            regardless of their current strike count. Use this for severe
            violations (scams, threats, illegal content) as well as
            third-strike escalations.
          </p>
          <ReportedUserStrikesSummary
            userName={userName}
            strikes={detail?.reported_user_strikes}
            loading={loadingDetail}
          />
          <Label className='flex flex-col items-start gap-1.5'>
            <span>Reason (shown to the user, never mention the reporter)</span>
            <Textarea value={reason} onChange={(e) => setReason(e.target.value)} autoFocus />
          </Label>
        </div>
      }
    />
  )
}
