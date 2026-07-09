import { type ColumnDef } from '@tanstack/react-table'
import { CheckCircle2, XCircle } from 'lucide-react'
import { cn } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import { DataTableColumnHeader } from '@/components/data-table'
import { LongText } from '@/components/long-text'
import { marketTypeOptions } from '../data/data'
import { type Market } from '../data/schema'
import { DataTableRowActions } from './data-table-row-actions'

export const marketsColumns: ColumnDef<Market>[] = [
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
    accessorKey: 'type',
    header: ({ column }) => <DataTableColumnHeader column={column} title='Type' />,
    cell: ({ row }) => {
      const type = row.getValue('type') as string
      const typeInfo = marketTypeOptions.find((t) => t.value === type)
      return <Badge variant='outline'>{typeInfo?.label ?? type}</Badge>
    },
    enableSorting: false,
  },
  {
    accessorKey: 'barangay',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Barangay' />
    ),
    cell: ({ row }) => <div>{row.getValue('barangay') ?? '—'}</div>,
    meta: { className: 'hidden md:table-cell' },
  },
  {
    accessorKey: 'municipality',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Municipality' />
    ),
    cell: ({ row }) => <div>{row.getValue('municipality') ?? '—'}</div>,
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
    accessorKey: 'tindahan_count',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Stalls' />
    ),
    cell: ({ row }) => <div>{row.getValue('tindahan_count') ?? 0}</div>,
    meta: { className: 'hidden lg:table-cell' },
  },
  {
    accessorKey: 'prices_count',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Prices' />
    ),
    cell: ({ row }) => <div>{row.getValue('prices_count') ?? 0}</div>,
    meta: { className: 'hidden lg:table-cell' },
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
