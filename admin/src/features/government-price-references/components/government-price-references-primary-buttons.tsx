import { Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useGovernmentPriceReferences } from './government-price-references-provider'

export function GovernmentPriceReferencesPrimaryButtons() {
  const { setOpen } = useGovernmentPriceReferences()
  return (
    <div className='flex gap-2'>
      <Button className='space-x-1' onClick={() => setOpen('add')}>
        <span>Add Reference</span> <Plus size={18} />
      </Button>
    </div>
  )
}
