import { type ColumnDef } from '@tanstack/react-table'
import { CheckCircle2, XCircle } from 'lucide-react'
import { cn } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import { DataTableColumnHeader } from '@/components/data-table'
import { LongText } from '@/components/long-text'
import { type MarketPrice } from '../data/schema'
import { DataTableRowActions } from './data-table-row-actions'

export const marketPricesColumns: ColumnDef<MarketPrice>[] = [
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
    accessorKey: 'item_name',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Item' />
    ),
    cell: ({ row }) => (
      <LongText className='max-w-48 ps-3'>{row.getValue('item_name')}</LongText>
    ),
    meta: {
      className: cn(
        'drop-shadow-[0_1px_2px_rgb(0_0_0_/_0.1)] dark:drop-shadow-[0_1px_2px_rgb(255_255_255_/_0.1)]',
        'inset-s-6 ps-0.5 max-md:sticky @4xl/content:table-cell @4xl/content:drop-shadow-none'
      ),
    },
    enableHiding: false,
  },
  {
    accessorKey: 'category',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Category' />
    ),
    cell: ({ row }) => {
      const category = row.getValue('category') as string | null
      return category ? (
        <Badge variant='outline' className='capitalize'>
          {category}
        </Badge>
      ) : (
        <span>-</span>
      )
    },
    enableSorting: false,
  },
  {
    accessorKey: 'price_per_unit',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Price' />
    ),
    cell: ({ row }) => {
      const price = Number(row.getValue('price_per_unit'))
      return <div>₱{price.toFixed(2)}</div>
    },
  },
  {
    accessorKey: 'unit',
    header: ({ column }) => <DataTableColumnHeader column={column} title='Unit' />,
    cell: ({ row }) => <div>{row.getValue('unit')}</div>,
  },
  {
    id: 'market',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Market' />
    ),
    cell: ({ row }) => <div>{row.original.market?.name ?? '-'}</div>,
    enableSorting: false,
    meta: { className: 'hidden md:table-cell' },
  },
  {
    id: 'tindahan',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Tindahan' />
    ),
    cell: ({ row }) => <div>{row.original.tindahan?.name ?? '-'}</div>,
    enableSorting: false,
    meta: { className: 'hidden md:table-cell' },
  },
  {
    id: 'is_available',
    accessorKey: 'is_available',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Available' />
    ),
    cell: ({ row }) =>
      row.getValue('is_available') ? (
        <CheckCircle2 size={16} className='text-primary' />
      ) : (
        <XCircle size={16} className='text-muted-foreground' />
      ),
    enableSorting: false,
  },
  {
    accessorKey: 'updated_at',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Updated' />
    ),
    cell: ({ row }) => (
      <div>{new Date(row.getValue('updated_at')).toLocaleDateString()}</div>
    ),
    meta: { className: 'hidden xl:table-cell' },
  },
  {
    id: 'actions',
    cell: DataTableRowActions,
  },
]
