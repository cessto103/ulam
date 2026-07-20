'use client'

import { toast } from 'sonner'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { type ContentReport } from '../data/schema'
import { useDeleteContentReport } from '../hooks/use-content-reports'

type ContentReportsDeleteDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: ContentReport
}

export function ContentReportsDeleteDialog({
  open,
  onOpenChange,
  currentRow,
}: ContentReportsDeleteDialogProps) {
  const { mutate: deleteReport, isPending } = useDeleteContentReport()

  const handleConfirm = () => {
    deleteReport(currentRow.id, {
      onSuccess: () => {
        onOpenChange(false)
        toast.success('Report deleted.')
      },
      onError: (error: any) => {
        toast.error(error?.response?.data?.message ?? 'Could not delete report.')
      },
    })
  }

  return (
    <ConfirmDialog
      open={open}
      onOpenChange={onOpenChange}
      handleConfirm={handleConfirm}
      disabled={isPending}
      destructive
      title='Delete report'
      confirmText='Delete'
      desc={
        <p>
          Are you sure you want to delete this report against{' '}
          <span className='font-bold'>
            {currentRow.reported_user?.name ?? '(unknown)'}
          </span>
          ? This action cannot be undone.
        </p>
      }
    />
  )
}
