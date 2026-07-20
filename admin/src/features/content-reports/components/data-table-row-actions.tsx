import { DotsHorizontalIcon } from '@radix-ui/react-icons'
import { type Row } from '@tanstack/react-table'
import { AlertTriangle, Ban, CheckCircle2, Clock, Trash2 } from 'lucide-react'
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
import { type ContentReport } from '../data/schema'
import { useDismissContentReport } from '../hooks/use-content-reports'
import { useContentReports } from './content-reports-provider'

type DataTableRowActionsProps = {
  row: Row<ContentReport>
}

export function DataTableRowActions({ row }: DataTableRowActionsProps) {
  const { setOpen, setCurrentRow } = useContentReports()
  const { mutate: dismiss, isPending: dismissing } = useDismissContentReport()

  const report = row.original
  const isPending = report.status === 'pending'

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
        {isPending && (
          <DropdownMenuItem
            onClick={() => {
              setCurrentRow(report)
              setOpen('warn')
            }}
          >
            Warn
            <DropdownMenuShortcut>
              <AlertTriangle size={16} />
            </DropdownMenuShortcut>
          </DropdownMenuItem>
        )}
        {isPending && (
          <DropdownMenuItem
            onClick={() => {
              setCurrentRow(report)
              setOpen('restrict')
            }}
            className='text-red-500!'
          >
            Restrict
            <DropdownMenuShortcut>
              <Clock size={16} />
            </DropdownMenuShortcut>
          </DropdownMenuItem>
        )}
        {isPending && (
          <DropdownMenuItem
            onClick={() => {
              setCurrentRow(report)
              setOpen('ban')
            }}
            className='text-red-500!'
          >
            Ban
            <DropdownMenuShortcut>
              <Ban size={16} />
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
