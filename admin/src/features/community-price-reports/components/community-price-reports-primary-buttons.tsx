import { Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useCommunityPriceReports } from './community-price-reports-provider'

export function CommunityPriceReportsPrimaryButtons() {
  const { setOpen } = useCommunityPriceReports()
  return (
    <div className='flex gap-2'>
      <Button className='space-x-1' onClick={() => setOpen('add')}>
        <span>Add Report</span> <Plus size={18} />
      </Button>
    </div>
  )
}
