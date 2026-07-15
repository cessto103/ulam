import { type ColumnDef } from '@tanstack/react-table'
import { CheckCircle2, XCircle } from 'lucide-react'
import { cn } from '@/lib/utils'
import { Checkbox } from '@/components/ui/checkbox'
import { DataTableColumnHeader } from '@/components/data-table'
import { LongText } from '@/components/long-text'
import { type Tindahan } from '../data/schema'
import { DataTableRowActions } from './data-table-row-actions'

export const tindahanColumns: ColumnDef<Tindahan>[] = [
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
    accessorKey: 'name',
    header: ({ column }) => <DataTableColumnHeader column={column} title='Name' />,
    cell: ({ row }) => (
      <LongText className='max-w-48 ps-3'>{row.getValue('name')}</LongText>
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
    id: 'market',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Market' />
    ),
    cell: ({ row }) => <div>{row.original.market?.name ?? '-'}</div>,
    enableSorting: false,
  },
  {
    accessorKey: 'type',
    header: ({ column }) => <DataTableColumnHeader column={column} title='Type' />,
    cell: ({ row }) => <div>{row.getValue('type') ?? '-'}</div>,
    meta: { className: 'hidden md:table-cell' },
  },
  {
    accessorKey: 'municipality',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Municipality' />
    ),
    cell: ({ row }) => <div>{row.getValue('municipality') ?? '-'}</div>,
  },
  {
    id: 'is_active',
    accessorKey: 'is_active',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Active' />
    ),
    cell: ({ row }) =>
      row.getValue('is_active') ? (
        <CheckCircle2 size={16} className='text-primary' />
      ) : (
        <XCircle size={16} className='text-muted-foreground' />
      ),
    enableSorting: false,
  },
  {
    id: 'is_verified',
    accessorKey: 'is_verified',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Verified' />
    ),
    cell: ({ row }) =>
      row.getValue('is_verified') ? (
        <CheckCircle2 size={16} className='text-primary' />
      ) : (
        <XCircle size={16} className='text-muted-foreground' />
      ),
    enableSorting: false,
  },
  {
    accessorKey: 'created_at',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Created' />
    ),
    cell: ({ row }) => (
      <div>{new Date(row.getValue('created_at')).toLocaleDateString()}</div>
    ),
    meta: { className: 'hidden xl:table-cell' },
  },
  {
    id: 'actions',
    cell: DataTableRowActions,
  },
]
