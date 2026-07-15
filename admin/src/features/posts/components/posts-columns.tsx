import { type ColumnDef } from '@tanstack/react-table'
import { BadgeCheck } from 'lucide-react'
import { cn } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import { DataTableColumnHeader } from '@/components/data-table'
import { LongText } from '@/components/long-text'
import { postTypes } from '../data/data'
import { type Post } from '../data/schema'
import { DataTableRowActions } from './data-table-row-actions'

export const postsColumns: ColumnDef<Post>[] = [
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
    accessorKey: 'id',
    header: ({ column }) => <DataTableColumnHeader column={column} title='ID' />,
    cell: ({ row }) => <div className='w-10 ps-3'>{row.getValue('id')}</div>,
    meta: {
      className: 'inset-s-6 ps-0.5 max-md:sticky @4xl/content:table-cell',
    },
    enableHiding: false,
  },
  {
    id: 'author',
    accessorFn: (row) => row.user?.name,
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Author' />
    ),
    cell: ({ row }) => (
      <LongText className='max-w-36'>{row.original.user?.name}</LongText>
    ),
    enableSorting: false,
  },
  {
    accessorKey: 'post_type',
    header: ({ column }) => <DataTableColumnHeader column={column} title='Type' />,
    cell: ({ row }) => {
      const type = row.getValue('post_type') as string
      const typeInfo = postTypes.find((t) => t.value === type)
      return (
        <Badge variant='outline' className='gap-1 capitalize'>
          {typeInfo?.icon && <typeInfo.icon size={12} />}
          {typeInfo?.label ?? type}
        </Badge>
      )
    },
    enableSorting: false,
  },
  {
    accessorKey: 'body',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Body' />
    ),
    cell: ({ row }) => (
      <LongText className='max-w-72'>{row.getValue('body')}</LongText>
    ),
    enableSorting: false,
  },
  {
    accessorKey: 'municipality',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Municipality' />
    ),
    cell: ({ row }) => <div>{row.getValue('municipality') ?? '-'}</div>,
    meta: { className: 'hidden md:table-cell' },
    enableSorting: false,
  },
  {
    accessorKey: 'puso_count',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Puso' />
    ),
    cell: ({ row }) => <div>{row.getValue('puso_count')}</div>,
    meta: { className: 'hidden lg:table-cell' },
    enableSorting: false,
  },
  {
    accessorKey: 'comments_count',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Comments' />
    ),
    cell: ({ row }) => <div>{row.getValue('comments_count')}</div>,
    meta: { className: 'hidden lg:table-cell' },
    enableSorting: false,
  },
  {
    id: 'is_sponsored',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Sponsored' />
    ),
    cell: ({ row }) =>
      row.original.is_sponsored ? (
        <Badge variant='default' className='gap-1'>
          <BadgeCheck size={12} /> Sponsored
        </Badge>
      ) : (
        <span className='text-muted-foreground'>-</span>
      ),
    enableSorting: false,
  },
  {
    accessorKey: 'created_at',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Created' />
    ),
    cell: ({ row }) => (
      <div className='text-nowrap'>
        {new Date(row.getValue('created_at')).toLocaleDateString()}
      </div>
    ),
    meta: { className: 'hidden md:table-cell' },
    enableSorting: false,
  },
  {
    id: 'actions',
    cell: DataTableRowActions,
  },
]
