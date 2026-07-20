'use client'

import { useState } from 'react'
import { toast } from 'sonner'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { type ContentReport } from '../data/schema'
import { useContentReport, useWarnReportedUser } from '../hooks/use-content-reports'
import { ReportedUserStrikesSummary } from './reported-user-strikes-summary'

type ContentReportsWarnDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: ContentReport
}

export function ContentReportsWarnDialog({
  open,
  onOpenChange,
  currentRow,
}: ContentReportsWarnDialogProps) {
  const [reason, setReason] = useState(
    currentRow.reason + (currentRow.details ? `: ${currentRow.details}` : '')
  )
  const { data: detail, isLoading: loadingDetail } = useContentReport(open ? currentRow.id : null)
  const { mutate: warn, isPending } = useWarnReportedUser()

  const userName = currentRow.reported_user?.name ?? 'this user'

  const handleConfirm = () => {
    if (!reason.trim()) return
    warn(
      { id: currentRow.id, reason: reason.trim() },
      {
        onSuccess: () => {
          onOpenChange(false)
          toast.success(`Warned ${userName}.`)
        },
        onError: (error: any) =>
          toast.error(error?.response?.data?.message ?? 'Could not warn this user.'),
      }
    )
  }

  return (
    <ConfirmDialog
      open={open}
      onOpenChange={onOpenChange}
      handleConfirm={handleConfirm}
      disabled={!reason.trim() || isPending}
      title='Warn user'
      confirmText='Send warning'
      desc={
        <div className='space-y-3'>
          <p>
            This sends <b>{userName}</b> a warning notification. No functional
            restriction is applied.
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
