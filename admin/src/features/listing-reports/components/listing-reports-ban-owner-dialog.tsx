'use client'

import { toast } from 'sonner'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { reportableTypeLabel } from '../data/data'
import { type ListingReport } from '../data/schema'
import { useBanListingOwner } from '../hooks/use-listing-reports'

type ListingReportsBanOwnerDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: ListingReport
}

export function ListingReportsBanOwnerDialog({
  open,
  onOpenChange,
  currentRow,
}: ListingReportsBanOwnerDialogProps) {
  const { mutate: banOwner, isPending } = useBanListingOwner()

  const handleConfirm = () => {
    banOwner(currentRow.id, {
      onSuccess: (response) => {
        onOpenChange(false)
        toast.success(response.data?.message ?? 'Owner banned.')
      },
      onError: (error: any) => {
        toast.error(
          error?.response?.data?.message ?? 'Could not ban this owner.'
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
      title='Ban owner'
      confirmText='Ban owner'
      desc={
        <p>
          This bans the user who owns{' '}
          <span className='font-bold'>
            {currentRow.reportable?.name ?? 'this listing'}
          </span>{' '}
          ({reportableTypeLabel(currentRow.reportable_type)}) from the app
          entirely. This cannot be undone from here.
        </p>
      }
    />
  )
}
