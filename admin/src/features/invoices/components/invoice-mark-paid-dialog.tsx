'use client'

import { AlertTriangle } from 'lucide-react'
import { toast } from 'sonner'
import { useQuery } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { type Invoice } from '../data/schema'
import { useMarkInvoiceAsPaid } from '../hooks/use-invoices'

type InvoiceMarkPaidDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: Invoice
}

export function InvoiceMarkPaidDialog({ open, onOpenChange, currentRow }: InvoiceMarkPaidDialogProps) {
  const { mutate: markPaid, isPending } = useMarkInvoiceAsPaid()

  // Cheap, non-blocking check -- issuing still works with blank business
  // settings (so the flow can be tested ahead of registration), this just
  // warns before a real, permanent number gets burned on a real deal.
  const { data: bizSettings } = useQuery({
    queryKey: ['admin-business-settings'],
    queryFn: async () => (await apiClient.get('/admin/business-settings')).data,
    enabled: open,
  })
  const missingBizDetails = open && bizSettings && (!bizSettings.biz_registered_name || !bizSettings.biz_tin)

  return (
    <ConfirmDialog
      open={open}
      onOpenChange={onOpenChange}
      handleConfirm={() => {
        markPaid(currentRow.id, {
          onSuccess: () => {
            onOpenChange(false)
            toast.success('Invoice marked as paid and issued.')
          },
          onError: (error: any) => {
            toast.error(error?.response?.data?.message ?? 'Could not mark invoice as paid.')
          },
        })
      }}
      isLoading={isPending}
      title='Mark as paid?'
      desc={
        <div className='space-y-3'>
          <p>
            <span className='font-bold'>{currentRow.buyer_name}</span> — {currentRow.description} — ₱
            {Number(currentRow.amount).toFixed(2)}
          </p>
          <p>
            This assigns the next official invoice number and permanently locks this invoice's PDF.
            It cannot be undone or edited afterward — only voided.
          </p>
          {missingBizDetails && (
            <Alert>
              <AlertTriangle className='size-4' />
              <AlertTitle>Business details aren't filled in yet</AlertTitle>
              <AlertDescription>
                This will still issue a real, permanent invoice number. Fill in Business & Tax
                Settings first if this is a real deal, not a test.
              </AlertDescription>
            </Alert>
          )}
        </div>
      }
      confirmText='Mark as paid'
    />
  )
}
