'use client'

import { useState } from 'react'
import { AlertTriangle } from 'lucide-react'
import { toast } from 'sonner'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { type Market } from '../data/schema'
import { useDeleteMarket } from '../hooks/use-markets'

type MarketDeleteDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: Market
}

export function MarketsDeleteDialog({
  open,
  onOpenChange,
  currentRow,
}: MarketDeleteDialogProps) {
  const [value, setValue] = useState('')
  const { mutate: deleteMarket, isPending } = useDeleteMarket()

  const handleDelete = () => {
    if (value.trim() !== currentRow.name) return

    deleteMarket(currentRow.id, {
      onSuccess: () => {
        onOpenChange(false)
        toast.success(`Deleted ${currentRow.name}.`)
      },
      onError: (error: any) => {
        toast.error(error?.response?.data?.message ?? 'Could not delete market.')
      },
    })
  }

  return (
    <ConfirmDialog
      open={open}
      onOpenChange={onOpenChange}
      form='markets-delete-form'
      disabled={value.trim() !== currentRow.name || isPending}
      title={
        <span className='text-destructive'>
          <AlertTriangle
            className='me-1 inline-block stroke-destructive'
            size={18}
          />{' '}
          Delete Market
        </span>
      }
      desc={
        <form
          id='markets-delete-form'
          onSubmit={(e) => {
            e.preventDefault()
            handleDelete()
          }}
          className='space-y-4'
        >
          <p className='mb-2'>
            Are you sure you want to delete{' '}
            <span className='font-bold'>{currentRow.name}</span>?
            <br />
            This action will permanently remove the market and cannot be
            undone.
          </p>

          <Label className='my-2'>
            Name:
            <Input
              value={value}
              onChange={(e) => setValue(e.target.value)}
              placeholder='Enter market name to confirm deletion.'
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
