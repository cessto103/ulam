import { type ColumnDef } from '@tanstack/react-table'
import { BadgeCheck, Circle } from 'lucide-react'
import { cn } from '@/lib/utils'
import { Checkbox } from '@/components/ui/checkbox'
import { DataTableColumnHeader } from '@/components/data-table'
import { LongText } from '@/components/long-text'
import { type CommunityPriceReport } from '../data/schema'
import { DataTableRowActions } from './data-table-row-actions'

export const communityPriceReportsColumns: ColumnDef<CommunityPriceReport>[] = [
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
      <LongText className='max-w-36 ps-3'>{row.getValue('item_name')}</LongText>
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
    id: 'reporter',
    accessorFn: (row) => row.user?.name ?? '—',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Reporter' />
    ),
    cell: ({ row }) => <div>{row.original.user?.name ?? '—'}</div>,
    enableSorting: false,
  },
  {
    accessorKey: 'category',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Category' />
    ),
    cell: ({ row }) => (
      <div className='capitalize'>{row.getValue('category') ?? '—'}</div>
    ),
    meta: { className: 'hidden md:table-cell' },
    enableSorting: false,
  },
  {
    accessorKey: 'reported_price',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Price' />
    ),
    cell: ({ row }) => (
      <div>₱{Number(row.getValue('reported_price')).toFixed(2)}</div>
    ),
  },
  {
    accessorKey: 'unit',
    header: ({ column }) => <DataTableColumnHeader column={column} title='Unit' />,
    cell: ({ row }) => <div>{row.getValue('unit')}</div>,
    enableSorting: false,
  },
  {
    accessorKey: 'municipality',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Municipality' />
    ),
    cell: ({ row }) => <div>{row.getValue('municipality') ?? '—'}</div>,
    meta: { className: 'hidden md:table-cell' },
    enableSorting: false,
  },
  {
    accessorKey: 'upvotes',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Upvotes' />
    ),
    cell: ({ row }) => <div>{row.getValue('upvotes')}</div>,
    meta: { className: 'hidden lg:table-cell' },
  },
  {
    accessorKey: 'downvotes',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Downvotes' />
    ),
    cell: ({ row }) => <div>{row.getValue('downvotes')}</div>,
    meta: { className: 'hidden lg:table-cell' },
  },
  {
    accessorKey: 'is_verified',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Verified' />
    ),
    cell: ({ row }) =>
      row.getValue('is_verified') ? (
        <BadgeCheck size={16} className='text-primary' />
      ) : (
        <Circle size={16} className='text-muted-foreground' />
      ),
    enableSorting: false,
  },
  {
    accessorKey: 'created_at',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Reported' />
    ),
    cell: ({ row }) => (
      <div>{new Date(row.getValue('created_at')).toLocaleDateString()}</div>
    ),
    meta: { className: 'hidden lg:table-cell' },
  },
  {
    id: 'actions',
    cell: DataTableRowActions,
  },
]
