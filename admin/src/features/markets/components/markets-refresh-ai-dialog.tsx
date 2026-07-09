'use client'

import { Sparkles } from 'lucide-react'
import { toast } from 'sonner'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { type Market } from '../data/schema'
import { useRefreshMarketAi } from '../hooks/use-markets'

type MarketsRefreshAiDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: Market
}

export function MarketsRefreshAiDialog({
  open,
  onOpenChange,
  currentRow,
}: MarketsRefreshAiDialogProps) {
  const { mutate: refreshAi, isPending } = useRefreshMarketAi()

  const handleConfirm = () => {
    refreshAi(currentRow.id, {
      onSuccess: (response) => {
        onOpenChange(false)
        toast.success(
          response.data?.message ?? `Refreshed prices for ${currentRow.name}.`
        )
      },
      onError: (error: any) => {
        toast.error(
          error?.response?.data?.message ?? 'Could not refresh prices via AI.'
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
          <Sparkles size={18} /> Refresh via AI
        </span>
      }
      confirmText={isPending ? 'Refreshing…' : 'Refresh'}
      desc={
        <div className='space-y-2'>
          <p>
            This makes a real API call (~$0.01-0.02) to search the web for
            current prices at <span className='font-bold'>{currentRow.name}</span>
            .
          </p>
          <p>
            This uses a live Claude API + web-search call and costs real
            money every time it runs. The request can take several seconds
            to complete.
          </p>
        </div>
      }
    />
  )
}
