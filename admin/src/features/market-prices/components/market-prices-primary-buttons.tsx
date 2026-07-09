import { Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useMarketPrices } from './market-prices-provider'

export function MarketPricesPrimaryButtons() {
  const { setOpen } = useMarketPrices()
  return (
    <div className='flex gap-2'>
      <Button className='space-x-1' onClick={() => setOpen('add')}>
        <span>Add Price</span> <Plus size={18} />
      </Button>
    </div>
  )
}
