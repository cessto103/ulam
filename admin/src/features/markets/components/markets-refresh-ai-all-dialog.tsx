'use client'

import { Sparkles } from 'lucide-react'
import { toast } from 'sonner'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { useRefreshAllMarketsAi } from '../hooks/use-markets'

type MarketsRefreshAiAllDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
}

export function MarketsRefreshAiAllDialog({
  open,
  onOpenChange,
}: MarketsRefreshAiAllDialogProps) {
  const { mutate: refreshAll, isPending } = useRefreshAllMarketsAi()

  const handleConfirm = () => {
    refreshAll(undefined, {
      onSuccess: (response) => {
        onOpenChange(false)
        toast.success(response.data?.message ?? 'Refreshed all active markets.')
      },
      onError: (error: any) => {
        toast.error(
          error?.response?.data?.message ?? 'Could not refresh market prices.'
        )
      },
    })
  }

  return (
    <ConfirmDialog
      open={open}
      onOpenChange={onOpenChange}
      handleConfirm={handleConfirm}
      disabled={isPending}
      isLoading={isPending}
      title={
        <span className='flex items-center gap-1.5'>
          <Sparkles size={18} /> Refresh All Market Prices
        </span>
      }
      confirmText={isPending ? 'Refreshing…' : 'Refresh All'}
      desc={
        <div className='space-y-2'>
          <p>
            This runs the same AI price lookup as the per-market "Refresh via
            AI" action, but for <span className='font-bold'>every active market</span> in
            one go — the same job that already runs automatically every day
            at 2 AM.
          </p>
          <p>
            Each market is a separate live Claude API + web-search call
            (~$0.01-0.02 each), run one after another. The more active
            markets you have, the longer this takes and the more it costs —
            this can take a while and shouldn't be run repeatedly.
          </p>
        </div>
      }
    />
  )
}
