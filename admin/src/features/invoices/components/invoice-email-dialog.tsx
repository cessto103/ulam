import { useEffect, useState } from 'react'
import { Loader2, Mail } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { type Invoice } from '../data/schema'
import { useEmailInvoice } from '../hooks/use-invoices'

type Props = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: Invoice
}

export function InvoiceEmailDialog({ open, onOpenChange, currentRow }: Props) {
  const emailInvoice = useEmailInvoice()
  const [to, setTo] = useState('')

  useEffect(() => {
    if (open) setTo(currentRow.buyer_email ?? '')
  }, [open, currentRow.buyer_email])

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Email this invoice</DialogTitle>
          <DialogDescription>
            {currentRow.status === 'draft'
              ? 'Sends the current draft as a payment request PDF.'
              : `Sends the official invoice ${currentRow.invoice_number} PDF.`}
          </DialogDescription>
        </DialogHeader>

        <div className='space-y-1.5'>
          <Label>Send to</Label>
          <Input type='email' value={to} onChange={(e) => setTo(e.target.value)} placeholder='advertiser@example.com' />
        </div>

        <DialogFooter>
          <Button variant='outline' onClick={() => onOpenChange(false)}>
            Cancel
          </Button>
          <Button
            disabled={!to.trim() || emailInvoice.isPending}
            onClick={() => {
              emailInvoice.mutate(
                { id: currentRow.id, to: to.trim() },
                {
                  onSuccess: (res) => {
                    toast.success(res.data.message)
                    onOpenChange(false)
                  },
                  onError: (error: any) => toast.error(error?.response?.data?.message ?? 'Could not send the email.'),
                }
              )
            }}
          >
            {emailInvoice.isPending ? <Loader2 className='animate-spin' /> : <Mail />}
            Send
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
