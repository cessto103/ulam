import { Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useInvoices } from './invoices-provider'

export function InvoicesPrimaryButtons() {
  const { setOpen } = useInvoices()
  return (
    <div className='flex gap-2'>
      <Button className='space-x-1' onClick={() => setOpen('add')}>
        <span>New Invoice</span> <Plus size={18} />
      </Button>
    </div>
  )
}
