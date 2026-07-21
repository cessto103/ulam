'use client'

import { useState } from 'react'
import { AlertTriangle } from 'lucide-react'
import { toast } from 'sonner'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { type Invoice } from '../data/schema'
import { useDeleteInvoice } from '../hooks/use-invoices'

type InvoiceDeleteDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: Invoice
}

export function InvoiceDeleteDialog({ open, onOpenChange, currentRow }: InvoiceDeleteDialogProps) {
  const [value, setValue] = useState('')
  const { mutate: deleteInvoice, isPending } = useDeleteInvoice()

  const handleDelete = () => {
    if (value.trim() !== currentRow.buyer_name) return

    deleteInvoice(currentRow.id, {
      onSuccess: () => {
        onOpenChange(false)
        toast.success('Draft invoice deleted.')
      },
      onError: (error: any) => {
        toast.error(error?.response?.data?.message ?? 'Could not delete invoice.')
      },
    })
  }

  return (
    <ConfirmDialog
      open={open}
      onOpenChange={onOpenChange}
      form='invoice-delete-form'
      disabled={value.trim() !== currentRow.buyer_name || isPending}
      title={
        <span className='text-destructive'>
          <AlertTriangle className='me-1 inline-block stroke-destructive' size={18} /> Delete Draft Invoice
        </span>
      }
      desc={
        <form
          id='invoice-delete-form'
          onSubmit={(e) => {
            e.preventDefault()
            handleDelete()
          }}
          className='space-y-4'
        >
          <p className='mb-2'>
            Are you sure you want to delete the draft invoice for{' '}
            <span className='font-bold'>{currentRow.buyer_name}</span>?
            <br />
            This only works for drafts — it never had an official number, so nothing is lost. This
            cannot be undone.
          </p>

          <Label className='my-2'>
            Buyer name:
            <Input
              value={value}
              onChange={(e) => setValue(e.target.value)}
              placeholder='Enter the buyer name to confirm deletion.'
              autoFocus
            />
          </Label>

          <Alert variant='destructive'>
            <AlertTitle>Warning!</AlertTitle>
            <AlertDescription>Please be careful, this operation can not be rolled back.</AlertDescription>
          </Alert>
        </form>
      }
      confirmText='Delete'
      destructive
    />
  )
}
