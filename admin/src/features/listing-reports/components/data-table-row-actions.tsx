import { DotsHorizontalIcon } from '@radix-ui/react-icons'
import { type Row } from '@tanstack/react-table'
import { Ban, CheckCircle2, EyeOff, Trash2 } from 'lucide-react'
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
import { type ListingReport } from '../data/schema'
import { useDismissListingReport } from '../hooks/use-listing-reports'
import { useListingReports } from './listing-reports-provider'

type DataTableRowActionsProps = {
  row: Row<ListingReport>
}

export function DataTableRowActions({ row }: DataTableRowActionsProps) {
  const { setOpen, setCurrentRow } = useListingReports()
  const { mutate: dismiss, isPending: dismissing } = useDismissListingReport()

  const report = row.original
  const isPending = report.status === 'pending'
  const hasListing = report.reportable !== null

  const handleDismiss = () => {
    dismiss(report.id, {
      onSuccess: () => toast.success('Report dismissed.'),
      onError: (error: any) =>
        toast.error(error?.response?.data?.message ?? 'Could not dismiss report.'),
    })
  }

  return (
    <DropdownMenu modal={false}>
      <DropdownMenuTrigger asChild>
        <Button
          variant='ghost'
          className='flex h-8 w-8 p-0 data-[state=open]:bg-muted'
        >
          <DotsHorizontalIcon className='h-4 w-4' />
          <span className='sr-only'>Open menu</span>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align='end' className='w-48'>
        {isPending && hasListing && (
          <DropdownMenuItem
            onClick={() => {
              setCurrentRow(report)
              setOpen('ban-owner')
            }}
            className='text-red-500!'
          >
            Ban owner
            <DropdownMenuShortcut>
              <Ban size={16} />
            </DropdownMenuShortcut>
          </DropdownMenuItem>
        )}
        {isPending && hasListing && (
          <DropdownMenuItem
            onClick={() => {
              setCurrentRow(report)
              setOpen('deactivate')
            }}
            className='text-red-500!'
          >
            Deactivate listing
            <DropdownMenuShortcut>
              <EyeOff size={16} />
            </DropdownMenuShortcut>
          </DropdownMenuItem>
        )}
        {isPending && (
          <DropdownMenuItem onClick={handleDismiss} disabled={dismissing}>
            Dismiss
            <DropdownMenuShortcut>
              <CheckCircle2 size={16} />
            </DropdownMenuShortcut>
          </DropdownMenuItem>
        )}
        {isPending && <DropdownMenuSeparator />}
        <DropdownMenuItem
          onClick={() => {
            setCurrentRow(report)
            setOpen('delete')
          }}
          className='text-red-500!'
        >
          Delete
          <DropdownMenuShortcut>
            <Trash2 size={16} />
          </DropdownMenuShortcut>
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  )
}
