import { useState } from 'react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { useRefundPayment, type RefundReason } from '../hooks/use-refund-payment'
import { type Payment } from '../data/schema'

const REASONS: { value: RefundReason; label: string }[] = [
  { value: 'requested_by_customer', label: 'Requested by customer' },
  { value: 'duplicate', label: 'Duplicate payment' },
  { value: 'fraudulent', label: 'Fraudulent' },
  { value: 'others', label: 'Other' },
]

type Props = {
  payment: Payment | null
  onOpenChange: (open: boolean) => void
}

export function RefundPaymentDialog({ payment, onOpenChange }: Props) {
  const refund = useRefundPayment()
  const [amountPesos, setAmountPesos] = useState('')
  const [reason, setReason] = useState<RefundReason>('requested_by_customer')

  const open = !!payment

  // Reset the form each time a new payment is targeted.
  const onChange = (next: boolean) => {
    if (next && payment) {
      setAmountPesos((payment.amount / 100).toFixed(2))
      setReason('requested_by_customer')
    }
    onOpenChange(next)
  }

  const amountCentavos = Math.round(Number(amountPesos) * 100)
  const valid =
    payment !== null &&
    Number.isFinite(amountCentavos) &&
    amountCentavos > 0 &&
    amountCentavos <= payment.amount

  return (
    <Dialog open={open} onOpenChange={onChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Refund payment</DialogTitle>
          <DialogDescription>
            {payment?.user?.name} · {payment?.plan_type} · Ref{' '}
            <span className='font-mono'>{payment?.provider_payment_id}</span>
            <br />
            This calls PayMongo directly — you do not get the transaction fee
            back, and e-wallet refunds can take a few business days to land.
          </DialogDescription>
        </DialogHeader>

        <div className='space-y-3'>
          <div className='space-y-1.5'>
            <Label>Refund amount (₱)</Label>
            <Input
              type='number'
              min={0.01}
              step={0.01}
              value={amountPesos}
              onChange={(e) => setAmountPesos(e.target.value)}
            />
            {payment && (
              <p className='text-xs text-muted-foreground'>
                Max ₱{(payment.amount / 100).toFixed(2)}
              </p>
            )}
          </div>
          <div className='space-y-1.5'>
            <Label>Reason</Label>
            <Select
              value={reason}
              onValueChange={(v) => setReason(v as RefundReason)}
            >
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {REASONS.map((r) => (
                  <SelectItem key={r.value} value={r.value}>
                    {r.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        </div>

        <DialogFooter>
          <Button variant='outline' onClick={() => onChange(false)}>
            Cancel
          </Button>
          <Button
            variant='destructive'
            disabled={!valid || refund.isPending}
            onClick={() => {
              if (!payment) return
              refund.mutate(
                { paymentId: payment.id, amount: amountCentavos, reason },
                {
                  onSuccess: () => {
                    toast.success('Refund issued.')
                    onChange(false)
                  },
                  onError: (error: any) =>
                    toast.error(
                      error?.response?.data?.message ?? 'Could not refund.'
                    ),
                }
              )
            }}
          >
            Refund via PayMongo
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
