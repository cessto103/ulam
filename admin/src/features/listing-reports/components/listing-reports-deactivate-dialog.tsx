'use client'

import { toast } from 'sonner'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { reportableTypeLabel } from '../data/data'
import { type ListingReport } from '../data/schema'
import { useDeactivateListing } from '../hooks/use-listing-reports'

type ListingReportsDeactivateDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: ListingReport
}

export function ListingReportsDeactivateDialog({
  open,
  onOpenChange,
  currentRow,
}: ListingReportsDeactivateDialogProps) {
  const { mutate: deactivateListing, isPending } = useDeactivateListing()

  const handleConfirm = () => {
    deactivateListing(currentRow.id, {
      onSuccess: () => {
        onOpenChange(false)
        toast.success(
          `Deactivated ${currentRow.reportable?.name ?? 'the listing'}.`
        )
      },
      onError: (error: any) => {
        toast.error(
          error?.response?.data?.message ?? 'Could not deactivate this listing.'
        )
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
      title='Deactivate listing'
      confirmText='Deactivate'
      desc={
        <p>
          This deactivates{' '}
          <span className='font-bold'>
            {currentRow.reportable?.name ?? 'this listing'}
          </span>{' '}
          ({reportableTypeLabel(currentRow.reportable_type)}), hiding it from
          the app.
        </p>
      }
    />
  )
}
