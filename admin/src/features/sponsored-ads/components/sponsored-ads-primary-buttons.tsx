import { Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useSponsoredAds } from './sponsored-ads-provider'

export function SponsoredAdsPrimaryButtons() {
  const { setOpen } = useSponsoredAds()
  return (
    <div className='flex gap-2'>
      <Button className='space-x-1' onClick={() => setOpen('add')}>
        <span>Add Sponsored Ad</span> <Plus size={18} />
      </Button>
    </div>
  )
}
