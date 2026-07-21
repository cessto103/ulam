import { DotsHorizontalIcon } from '@radix-ui/react-icons'
import { type Row } from '@tanstack/react-table'
import { Ban, CircleDollarSign, Download, Eye, Loader2, Mail, Pencil, Trash2 } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuShortcut,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { type Invoice } from '../data/schema'
import { useDownloadInvoicePdf } from '../hooks/use-invoices'
import { useInvoices } from './invoices-provider'

type DataTableRowActionsProps = {
  row: Row<Invoice>
}

export function DataTableRowActions({ row }: DataTableRowActionsProps) {
  const { setOpen, setCurrentRow } = useInvoices()
  const { mutate: download, isPending: downloading } = useDownloadInvoicePdf()
  const invoice = row.original

  const handleDownload = () => {
    download(
      { id: invoice.id, filename: `${invoice.invoice_number ?? 'draft-invoice-' + invoice.id}.pdf` },
      { onError: () => toast.error('Could not download the PDF.') }
    )
  }

  return (
    <DropdownMenu modal={false}>
      <DropdownMenuTrigger asChild>
        <Button variant='ghost' className='flex h-8 w-8 p-0 data-[state=open]:bg-muted'>
          <DotsHorizontalIcon className='h-4 w-4' />
          <span className='sr-only'>Open menu</span>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align='end' className='w-48'>
        {invoice.status === 'draft' ? (
          <DropdownMenuItem
            onClick={() => {
              setCurrentRow(invoice)
              setOpen('edit')
            }}
          >
            Edit
            <DropdownMenuShortcut><Pencil size={16} /></DropdownMenuShortcut>
          </DropdownMenuItem>
        ) : (
          <DropdownMenuItem
            onClick={() => {
              setCurrentRow(invoice)
              setOpen('view')
            }}
          >
            View
            <DropdownMenuShortcut><Eye size={16} /></DropdownMenuShortcut>
          </DropdownMenuItem>
        )}

        <DropdownMenuItem onClick={handleDownload} disabled={downloading}>
          Download PDF
          <DropdownMenuShortcut>{downloading ? <Loader2 className='animate-spin' size={16} /> : <Download size={16} />}</DropdownMenuShortcut>
        </DropdownMenuItem>

        <DropdownMenuItem
          onClick={() => {
            setCurrentRow(invoice)
            setOpen('email')
          }}
        >
          Email
          <DropdownMenuShortcut><Mail size={16} /></DropdownMenuShortcut>
        </DropdownMenuItem>

        <DropdownMenuSeparator />

        {invoice.status === 'draft' && (
          <>
            <DropdownMenuItem
              onClick={() => {
                setCurrentRow(invoice)
                setOpen('mark-paid')
              }}
            >
              Mark as paid
              <DropdownMenuShortcut><CircleDollarSign size={16} className='text-emerald-600' /></DropdownMenuShortcut>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem
              onClick={() => {
                setCurrentRow(invoice)
                setOpen('delete')
              }}
              className='text-red-500!'
            >
              Delete
              <DropdownMenuShortcut><Trash2 size={16} /></DropdownMenuShortcut>
            </DropdownMenuItem>
          </>
        )}

        {invoice.status === 'issued' && (
          <DropdownMenuItem
            onClick={() => {
              setCurrentRow(invoice)
              setOpen('void')
            }}
            className='text-red-500!'
          >
            Void
            <DropdownMenuShortcut><Ban size={16} /></DropdownMenuShortcut>
          </DropdownMenuItem>
        )}
      </DropdownMenuContent>
    </DropdownMenu>
  )
}
