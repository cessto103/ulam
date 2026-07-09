import { type ColumnDef } from '@tanstack/react-table'
import { cn } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import { DataTableColumnHeader } from '@/components/data-table'
import { LongText } from '@/components/long-text'
import { type PostComment } from '../data/schema'
import { DataTableRowActions } from './data-table-row-actions'

export const commentsColumns: ColumnDef<PostComment>[] = [
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
    id: 'author',
    accessorFn: (row) => row.user?.name,
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Author' />
    ),
    cell: ({ row }) => (
      <LongText className='max-w-36 ps-3'>{row.original.user?.name}</LongText>
    ),
    meta: {
      className: 'inset-s-6 ps-0.5 max-md:sticky @4xl/content:table-cell',
    },
    enableSorting: false,
    enableHiding: false,
  },
  {
    id: 'on_post',
    accessorFn: (row) => row.post?.body,
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='On Post' />
    ),
    cell: ({ row }) => (
      <LongText className='max-w-48 text-muted-foreground'>
        {row.original.post?.body}
      </LongText>
    ),
    enableSorting: false,
  },
  {
    accessorKey: 'body',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Comment' />
    ),
    cell: ({ row }) => (
      <LongText className='max-w-72'>{row.getValue('body')}</LongText>
    ),
    enableSorting: false,
  },
  {
    id: 'is_reply',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Reply' />
    ),
    cell: ({ row }) =>
      row.original.parent_id !== null ? (
        <Badge variant='outline'>Reply</Badge>
      ) : (
        <Badge variant='secondary'>Top-level</Badge>
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
