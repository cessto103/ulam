import { DotsHorizontalIcon } from '@radix-ui/react-icons'
import { type Row } from '@tanstack/react-table'
import { Megaphone, Power, PowerOff, Trash2 } from 'lucide-react'
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
import { type SponsoredAd } from '../data/schema'
import { useToggleEnabledSponsoredAd } from '../hooks/use-sponsored-ads'
import { useSponsoredAds } from './sponsored-ads-provider'

type DataTableRowActionsProps = {
  row: Row<SponsoredAd>
}

export function DataTableRowActions({ row }: DataTableRowActionsProps) {
  const { setOpen, setCurrentRow } = useSponsoredAds()
  const { mutate: toggleEnabled } = useToggleEnabledSponsoredAd()
  const ad = row.original

  const handleToggleEnabled = () => {
    toggleEnabled(
      { id: ad.id, is_enabled: !ad.is_enabled },
      {
        onSuccess: () => {
          toast.success(ad.is_enabled ? 'Ad disabled.' : 'Ad enabled.')
        },
        onError: (error: any) => {
          toast.error(error?.response?.data?.message ?? 'Could not update ad.')
        },
      }
    )
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
      <DropdownMenuContent align='end' className='w-44'>
        <DropdownMenuItem
          onClick={() => {
            setCurrentRow(ad)
            setOpen('edit')
          }}
        >
          Edit
          <DropdownMenuShortcut>
            <Megaphone size={16} />
          </DropdownMenuShortcut>
        </DropdownMenuItem>
        <DropdownMenuSeparator />
        <DropdownMenuItem onClick={handleToggleEnabled}>
          {ad.is_enabled ? 'Disable' : 'Enable'}
          <DropdownMenuShortcut>
            {ad.is_enabled ? <PowerOff size={16} /> : <Power size={16} />}
          </DropdownMenuShortcut>
        </DropdownMenuItem>
        <DropdownMenuSeparator />
        <DropdownMenuItem
          onClick={() => {
            setCurrentRow(ad)
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
