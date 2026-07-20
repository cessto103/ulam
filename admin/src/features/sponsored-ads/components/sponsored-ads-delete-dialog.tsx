'use client'

import { useState } from 'react'
import { AlertTriangle } from 'lucide-react'
import { toast } from 'sonner'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { type SponsoredAd } from '../data/schema'
import { useDeleteSponsoredAd } from '../hooks/use-sponsored-ads'

type SponsoredAdDeleteDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: SponsoredAd
}

export function SponsoredAdsDeleteDialog({
  open,
  onOpenChange,
  currentRow,
}: SponsoredAdDeleteDialogProps) {
  const [value, setValue] = useState('')
  const { mutate: deleteAd, isPending } = useDeleteSponsoredAd()

  const handleDelete = () => {
    if (value.trim() !== currentRow.product_name) return

    deleteAd(currentRow.id, {
      onSuccess: () => {
        onOpenChange(false)
        toast.success(`Deleted ${currentRow.product_name}.`)
      },
      onError: (error: any) => {
        toast.error(error?.response?.data?.message ?? 'Could not delete sponsored ad.')
      },
    })
  }

  return (
    <ConfirmDialog
      open={open}
      onOpenChange={onOpenChange}
      form='sponsored-ads-delete-form'
      disabled={value.trim() !== currentRow.product_name || isPending}
      title={
        <span className='text-destructive'>
          <AlertTriangle
            className='me-1 inline-block stroke-destructive'
            size={18}
          />{' '}
          Delete Sponsored Ad
        </span>
      }
      desc={
        <form
          id='sponsored-ads-delete-form'
          onSubmit={(e) => {
            e.preventDefault()
            handleDelete()
          }}
          className='space-y-4'
        >
          <p className='mb-2'>
            Are you sure you want to delete{' '}
            <span className='font-bold'>{currentRow.product_name}</span>?
            <br />
            This removes the ad and its photo permanently. This cannot be undone.
          </p>

          <Label className='my-2'>
            Product name:
            <Input
              value={value}
              onChange={(e) => setValue(e.target.value)}
              placeholder='Enter the product name to confirm deletion.'
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
