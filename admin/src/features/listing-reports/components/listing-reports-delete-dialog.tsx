'use client'

import { toast } from 'sonner'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { type ListingReport } from '../data/schema'
import { useDeleteListingReport } from '../hooks/use-listing-reports'

type ListingReportsDeleteDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: ListingReport
}

export function ListingReportsDeleteDialog({
  open,
  onOpenChange,
  currentRow,
}: ListingReportsDeleteDialogProps) {
  const { mutate: deleteReport, isPending } = useDeleteListingReport()

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
            {currentRow.reportable?.name ?? '(deleted)'}
          </span>
          ? This action cannot be undone.
        </p>
      }
    />
  )
}
