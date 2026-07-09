'use client'

import { useState } from 'react'
import { AlertTriangle } from 'lucide-react'
import { toast } from 'sonner'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { type CommunityPriceReport } from '../data/schema'
import { useDeleteCommunityPriceReport } from '../hooks/use-community-price-reports'

type CommunityPriceReportDeleteDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: CommunityPriceReport
}

export function CommunityPriceReportsDeleteDialog({
  open,
  onOpenChange,
  currentRow,
}: CommunityPriceReportDeleteDialogProps) {
  const [value, setValue] = useState('')
  const { mutate: deleteReport, isPending } = useDeleteCommunityPriceReport()

  const handleDelete = () => {
    if (value.trim() !== currentRow.item_name) return

    deleteReport(currentRow.id, {
      onSuccess: () => {
        onOpenChange(false)
        toast.success(`Deleted ${currentRow.item_name} report.`)
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
      form='community-price-reports-delete-form'
      disabled={value.trim() !== currentRow.item_name || isPending}
      title={
        <span className='text-destructive'>
          <AlertTriangle
            className='me-1 inline-block stroke-destructive'
            size={18}
          />{' '}
          Delete Price Report
        </span>
      }
      desc={
        <form
          id='community-price-reports-delete-form'
          onSubmit={(e) => {
            e.preventDefault()
            handleDelete()
          }}
          className='space-y-4'
        >
          <p className='mb-2'>
            Are you sure you want to delete the report for{' '}
            <span className='font-bold'>{currentRow.item_name}</span>?
            <br />
            This action cannot be undone.
          </p>

          <Label className='my-2'>
            Item name:
            <Input
              value={value}
              onChange={(e) => setValue(e.target.value)}
              placeholder='Enter item name to confirm deletion.'
              autoFocus
            />
          </Label>

          <Alert variant='destructive'>
            <AlertTitle>Warning!</AlertTitle>
            <AlertDescription>
              Please be careful, this operation can not be rolled back.
            </AlertDescription>
          </Alert>
        </form>
      }
      confirmText='Delete'
      destructive
    />
  )
}
