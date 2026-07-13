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
import { useRejectBoost, type Boost } from '../hooks/use-boosts'

type Props = {
  boost: Boost | null
  onOpenChange: (open: boolean) => void
}

export function RejectBoostDialog({ boost, onOpenChange }: Props) {
  const reject = useRejectBoost()
  const [reason, setReason] = useState('')

  const open = !!boost

  const onChange = (next: boolean) => {
    if (next) setReason('')
    onOpenChange(next)
  }

  return (
    <Dialog open={open} onOpenChange={onChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Reject boost payment</DialogTitle>
          <DialogDescription>
            {boost?.user?.name} · {boost?.target === 'recipe' ? 'Recipe' : 'Store'}:{' '}
            {boost?.target_name} · Ref{' '}
            <span className='font-mono'>{boost?.payment_reference}</span>
            <br />
            The seller will be notified with this reason so they can re-submit if it was a mistake.
          </DialogDescription>
        </DialogHeader>

        <div className='space-y-1.5'>
          <Label>Reason</Label>
          <Textarea
            rows={3}
            placeholder='e.g. Reference number not found in GCash records'
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
            disabled={!reason.trim() || reject.isPending}
            onClick={() => {
              if (!boost) return
              reject.mutate(
                { id: boost.id, reason: reason.trim() },
                {
                  onSuccess: () => {
                    toast.success('Boost submission rejected.')
                    onChange(false)
                  },
                  onError: (error: any) =>
                    toast.error(error?.response?.data?.message ?? 'Could not reject.'),
                }
              )
            }}
          >
            Reject
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
