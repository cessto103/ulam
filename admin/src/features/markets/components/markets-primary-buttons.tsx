import { Plus, Sparkles } from 'lucide-react'
import { Button } from '@/components/ui/button'
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from '@/components/ui/tooltip'
import { usePaymentSettingsQuery } from '@/features/monetization/hooks/use-monetization'
import { useMarkets } from './markets-provider'

export function MarketsPrimaryButtons() {
  const { setOpen } = useMarkets()
  const { data: settings } = usePaymentSettingsQuery()
  // Same switch that already gates prices:refresh-ai/gov on the backend
  // (and the per-market "Refresh via AI" action below) -- default to
  // enabled while settings are still loading, matching AppSetting::get()'s
  // own '1' default server-side.
  const aiRefreshDisabled = settings?.price_refresh_ai_enabled === '0'

  return (
    <div className='flex gap-2'>
      {aiRefreshDisabled ? (
        <Tooltip>
          <TooltipTrigger asChild>
            <span>
              <Button variant='outline' className='space-x-1' disabled>
                <span>Refresh All Prices</span> <Sparkles size={18} />
              </Button>
            </span>
          </TooltipTrigger>
          <TooltipContent>
            <p>AI price refresh is paused (Monetization &rarr; AI feature controls).</p>
          </TooltipContent>
        </Tooltip>
      ) : (
        <Button
          variant='outline'
          className='space-x-1'
          onClick={() => setOpen('refresh-ai-all')}
        >
          <span>Refresh All Prices</span> <Sparkles size={18} />
        </Button>
      )}
      <Button className='space-x-1' onClick={() => setOpen('add')}>
        <span>Add Market</span> <Plus size={18} />
      </Button>
    </div>
  )
}
