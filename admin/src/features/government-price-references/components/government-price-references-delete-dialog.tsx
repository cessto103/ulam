'use client'

import { useState } from 'react'
import { AlertTriangle } from 'lucide-react'
import { toast } from 'sonner'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { type GovernmentPriceReference } from '../data/schema'
import { useDeleteGovernmentPriceReference } from '../hooks/use-government-price-references'

type GovernmentPriceReferenceDeleteDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: GovernmentPriceReference
}

export function GovernmentPriceReferencesDeleteDialog({
  open,
  onOpenChange,
  currentRow,
}: GovernmentPriceReferenceDeleteDialogProps) {
  const [value, setValue] = useState('')
  const { mutate: deleteReference, isPending } =
    useDeleteGovernmentPriceReference()

  const handleDelete = () => {
    if (value.trim() !== currentRow.item_name) return

    deleteReference(currentRow.id, {
      onSuccess: () => {
        onOpenChange(false)
        toast.success(`Deleted ${currentRow.item_name} reference.`)
      },
      onError: (error: any) => {
        toast.error(
          error?.response?.data?.message ?? 'Could not delete reference.'
        )
      },
    })
  }

  return (
    <ConfirmDialog
      open={open}
      onOpenChange={onOpenChange}
      form='government-price-references-delete-form'
      disabled={value.trim() !== currentRow.item_name || isPending}
      title={
        <span className='text-destructive'>
          <AlertTriangle
            className='me-1 inline-block stroke-destructive'
            size={18}
          />{' '}
          Delete Price Reference
        </span>
      }
      desc={
        <form
          id='government-price-references-delete-form'
          onSubmit={(e) => {
            e.preventDefault()
            handleDelete()
          }}
          className='space-y-4'
        >
          <p className='mb-2'>
            Are you sure you want to delete the reference for{' '}
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
