import { Plus, Sparkles } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useMarkets } from './markets-provider'

export function MarketsPrimaryButtons() {
  const { setOpen } = useMarkets()
  return (
    <div className='flex gap-2'>
      <Button
        variant='outline'
        className='space-x-1'
        onClick={() => setOpen('refresh-ai-all')}
      >
        <span>Refresh All Prices</span> <Sparkles size={18} />
      </Button>
      <Button className='space-x-1' onClick={() => setOpen('add')}>
        <span>Add Market</span> <Plus size={18} />
      </Button>
    </div>
  )
}
