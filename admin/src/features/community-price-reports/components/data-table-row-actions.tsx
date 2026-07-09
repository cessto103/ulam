import { DotsHorizontalIcon } from '@radix-ui/react-icons'
import { type Row } from '@tanstack/react-table'
import { BadgeCheck, Pencil, Trash2 } from 'lucide-react'
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
import { type CommunityPriceReport } from '../data/schema'
import { useVerifyCommunityPriceReport } from '../hooks/use-community-price-reports'
import { useCommunityPriceReports } from './community-price-reports-provider'

type DataTableRowActionsProps = {
  row: Row<CommunityPriceReport>
}

export function DataTableRowActions({ row }: DataTableRowActionsProps) {
  const { setOpen, setCurrentRow } = useCommunityPriceReports()
  const { mutate: verifyReport, isPending } = useVerifyCommunityPriceReport()
  const isVerified = row.original.is_verified

  const handleVerify = () => {
    verifyReport(row.original.id, {
      onSuccess: () => {
        toast.success(`Marked ${row.original.item_name} as verified.`)
      },
      onError: (error: any) => {
        toast.error(
          error?.response?.data?.message ?? 'Could not verify report.'
        )
      },
    })
  }

  return (
    <>
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
        <DropdownMenuContent align='end' className='w-44'>
          <DropdownMenuItem
            onClick={() => {
              setCurrentRow(row.original)
              setOpen('edit')
            }}
          >
            Edit
            <DropdownMenuShortcut>
              <Pencil size={16} />
            </DropdownMenuShortcut>
          </DropdownMenuItem>
          {!isVerified && (
            <DropdownMenuItem onClick={handleVerify} disabled={isPending}>
              Mark verified
              <DropdownMenuShortcut>
                <BadgeCheck size={16} />
              </DropdownMenuShortcut>
            </DropdownMenuItem>
          )}
          <DropdownMenuSeparator />
          <DropdownMenuItem
            onClick={() => {
              setCurrentRow(row.original)
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
    </>
  )
}
