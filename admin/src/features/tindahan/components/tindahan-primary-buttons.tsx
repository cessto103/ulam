import { Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useTindahan } from './tindahan-provider'

export function TindahanPrimaryButtons() {
  const { setOpen } = useTindahan()
  return (
    <div className='flex gap-2'>
      <Button className='space-x-1' onClick={() => setOpen('add')}>
        <span>Add Tindahan</span> <Plus size={18} />
      </Button>
    </div>
  )
}
