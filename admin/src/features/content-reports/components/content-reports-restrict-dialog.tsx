'use client'

import { useState } from 'react'
import { toast } from 'sonner'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { type ContentReport } from '../data/schema'
import { useContentReport, useRestrictReportedUser } from '../hooks/use-content-reports'
import { ReportedUserStrikesSummary } from './reported-user-strikes-summary'

type ContentReportsRestrictDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: ContentReport
}

export function ContentReportsRestrictDialog({
  open,
  onOpenChange,
  currentRow,
}: ContentReportsRestrictDialogProps) {
  const [reason, setReason] = useState(
    currentRow.reason + (currentRow.details ? `: ${currentRow.details}` : '')
  )
  const { data: detail, isLoading: loadingDetail } = useContentReport(open ? currentRow.id : null)
  const { mutate: restrict, isPending } = useRestrictReportedUser()

  const userName = currentRow.reported_user?.name ?? 'this user'

  const handleConfirm = () => {
    if (!reason.trim()) return
    restrict(
      { id: currentRow.id, reason: reason.trim() },
      {
        onSuccess: () => {
          onOpenChange(false)
          toast.success(`Restricted ${userName} for 7 days.`)
        },
        onError: (error: any) =>
          toast.error(error?.response?.data?.message ?? 'Could not restrict this user.'),
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
      title='Restrict user'
      confirmText='Restrict 7 days'
      desc={
        <div className='space-y-3'>
          <p>
            This blocks <b>{userName}</b> from posting, commenting, reporting
            prices, and creating recipes/stores for 7 days. Budgeting,
            shopping lists, and meal plans stay available.
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
