import { type ColumnDef } from '@tanstack/react-table'
import { cn } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import { DataTableColumnHeader } from '@/components/data-table'
import { LongText } from '@/components/long-text'
import { type SponsoredAd } from '../data/schema'
import { DataTableRowActions } from './data-table-row-actions'

const STATUS_VARIANT: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
  running: 'default',
  scheduled: 'secondary',
  ended: 'outline',
  disabled: 'destructive',
}

export const sponsoredAdsColumns: ColumnDef<SponsoredAd>[] = [
  {
    id: 'select',
    header: ({ table }) => (
      <Checkbox
        checked={
          table.getIsAllPageRowsSelected() ||
          (table.getIsSomePageRowsSelected() && 'indeterminate')
        }
        onCheckedChange={(value) => table.toggleAllPageRowsSelected(!!value)}
        aria-label='Select all'
        className='translate-y-0.5'
      />
    ),
    meta: {
      className: cn('inset-s-0 z-10 rounded-tl-[inherit] max-md:sticky'),
    },
    cell: ({ row }) => (
      <Checkbox
        checked={row.getIsSelected()}
        onCheckedChange={(value) => row.toggleSelected(!!value)}
        aria-label='Select row'
        className='translate-y-0.5'
      />
    ),
    enableSorting: false,
    enableHiding: false,
  },
  {
    accessorKey: 'product_name',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Product' />
    ),
    cell: ({ row }) => (
      <LongText className='max-w-40 ps-3'>{row.getValue('product_name')}</LongText>
    ),
    meta: {
      className: cn(
        'drop-shadow-[0_1px_2px_rgb(0_0_0_/_0.1)] dark:drop-shadow-[0_1px_2px_rgb(255_255_255_/_0.1)]',
        'inset-s-6 ps-0.5 max-md:sticky @4xl/content:table-cell @4xl/content:drop-shadow-none'
      ),
    },
    enableSorting: false,
    enableHiding: false,
  },
  {
    accessorKey: 'company_name',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Company' />
    ),
    cell: ({ row }) => (
      <LongText className='max-w-36'>{row.getValue('company_name')}</LongText>
    ),
    enableSorting: false,
  },
  {
    accessorKey: 'display_status',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Status' />
    ),
    cell: ({ row }) => {
      const status = row.getValue('display_status') as string
      return (
        <Badge variant={STATUS_VARIANT[status] ?? 'outline'} className='capitalize'>
          {status}
        </Badge>
      )
    },
    enableSorting: false,
  },
  {
    id: 'flight',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Flight dates' />
    ),
    cell: ({ row }) => (
      <div className='text-sm whitespace-nowrap'>
        {new Date(row.original.start_date).toLocaleDateString()} to{' '}
        {new Date(row.original.end_date).toLocaleDateString()}
      </div>
    ),
    meta: { className: 'hidden md:table-cell' },
  },
  {
    accessorKey: 'amount_paid',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Paid' />
    ),
    cell: ({ row }) => (
      <div>₱{Number(row.getValue('amount_paid')).toFixed(2)}</div>
    ),
    meta: { className: 'hidden lg:table-cell' },
  },
  {
    accessorKey: 'impressions_count',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Impressions' />
    ),
    cell: ({ row }) => <div>{row.getValue('impressions_count')}</div>,
    meta: { className: 'hidden lg:table-cell' },
  },
  {
    accessorKey: 'clicks_count',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Clicks' />
    ),
    cell: ({ row }) => <div>{row.getValue('clicks_count')}</div>,
    meta: { className: 'hidden lg:table-cell' },
  },
  {
    id: 'actions',
    cell: DataTableRowActions,
  },
]
