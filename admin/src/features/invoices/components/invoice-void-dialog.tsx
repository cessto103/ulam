import { useState } from 'react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { type Invoice } from '../data/schema'
import { useVoidInvoice } from '../hooks/use-invoices'

type Props = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: Invoice
}

export function InvoiceVoidDialog({ open, onOpenChange, currentRow }: Props) {
  const voidInvoice = useVoidInvoice()
  const [reason, setReason] = useState('')

  const onChange = (next: boolean) => {
    if (next) setReason('')
    onOpenChange(next)
  }

  return (
    <Dialog open={open} onOpenChange={onChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Void invoice {currentRow.invoice_number}</DialogTitle>
          <DialogDescription>
            {currentRow.buyer_name} — ₱{Number(currentRow.amount).toFixed(2)}
            <br />
            The invoice number and its PDF are kept permanently, just marked cancelled. This cannot
            be undone.
          </DialogDescription>
        </DialogHeader>

        <div className='space-y-1.5'>
          <Label>Reason</Label>
          <Textarea
            rows={3}
            placeholder='e.g. Duplicate entry, deal fell through, wrong amount'
            value={reason}
            onChange={(e) => setReason(e.target.value)}
          />
        </div>

        <DialogFooter>
          <Button variant='outline' onClick={() => onChange(false)}>
            Cancel
          </Button>
          <Button
            variant='destructive'
            disabled={!reason.trim() || voidInvoice.isPending}
            onClick={() => {
              voidInvoice.mutate(
                { id: currentRow.id, reason: reason.trim() },
                {
                  onSuccess: () => {
                    toast.success('Invoice voided.')
                    onChange(false)
                  },
                  onError: (error: any) => toast.error(error?.response?.data?.message ?? 'Could not void invoice.'),
                }
              )
            }}
          >
            Void invoice
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
