import { type ColumnDef } from '@tanstack/react-table'
import { cn } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import { DataTableColumnHeader } from '@/components/data-table'
import { LongText } from '@/components/long-text'
import { contentTypeLabel } from '../data/data'
import { type ContentReport } from '../data/schema'
import { DataTableRowActions } from './data-table-row-actions'

export const contentReportsColumns: ColumnDef<ContentReport>[] = [
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
    id: 'reporter',
    accessorFn: (row) => row.reporter?.name ?? '-',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Reported by' />
    ),
    cell: ({ row }) => (
      <LongText className='max-w-36 ps-3'>
        {row.original.reporter?.name ?? '-'}
      </LongText>
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
    id: 'reported_user',
    accessorFn: (row) => row.reported_user?.name ?? '(unknown)',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Reported user' />
    ),
    cell: ({ row }) => (
      <LongText className='max-w-36'>
        {row.original.reported_user?.name ?? '(unknown)'}
      </LongText>
    ),
    enableSorting: false,
  },
  {
    id: 'content_type',
    accessorFn: (row) => contentTypeLabel(row.content_type),
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Type' />
    ),
    cell: ({ row }) => (
      <Badge variant='outline'>
        {contentTypeLabel(row.original.content_type)}
      </Badge>
    ),
    enableSorting: false,
  },
  {
    id: 'content',
    accessorFn: (row) => row.content_preview ?? '(deleted)',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Content' />
    ),
    cell: ({ row }) => (
      <LongText className='max-w-48'>
        {row.original.content_exists
          ? (row.original.content_preview ?? '-')
          : '(deleted)'}
      </LongText>
    ),
    enableSorting: false,
  },
  {
    accessorKey: 'reason',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Reason' />
    ),
    cell: ({ row }) => (
      <LongText className='max-w-60'>{row.getValue('reason')}</LongText>
    ),
    meta: { className: 'hidden md:table-cell' },
    enableSorting: false,
  },
  {
    accessorKey: 'status',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Status' />
    ),
    cell: ({ row }) => {
      const status = row.getValue('status') as string
      const variant =
        status === 'pending'
          ? 'destructive'
          : status === 'actioned'
            ? 'default'
            : 'secondary'
      return (
        <Badge variant={variant} className='capitalize'>
          {status}
        </Badge>
      )
    },
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
