import { type ColumnDef } from '@tanstack/react-table'
import { Badge } from '@/components/ui/badge'
import { DataTableColumnHeader } from '@/components/data-table'
import { LongText } from '@/components/long-text'
import { type WebhookEvent } from '../data/schema'

function statusClassName(status: string): string {
  if (status === 'failed') return 'bg-red-500/15 text-red-600 dark:text-red-400'
  if (status === 'processed') return 'bg-green-500/15 text-green-600 dark:text-green-400'
  if (status === 'ignored') return 'bg-muted text-muted-foreground'
  return ''
}

export const webhooksColumns: ColumnDef<WebhookEvent>[] = [
  {
    accessorKey: 'id',
    header: ({ column }) => <DataTableColumnHeader column={column} title='#' />,
    cell: ({ row }) => <div className='ps-3'>{row.getValue('id')}</div>,
    enableHiding: false,
  },
  {
    accessorKey: 'event_type',
    header: ({ column }) => <DataTableColumnHeader column={column} title='Event Type' />,
    cell: ({ row }) => <span className='font-mono text-xs'>{row.getValue('event_type')}</span>,
    enableSorting: false,
  },
  {
    accessorKey: 'status',
    header: ({ column }) => <DataTableColumnHeader column={column} title='Status' />,
    cell: ({ row }) => {
      const status = row.getValue('status') as string
      return (
        <Badge className={`capitalize ${statusClassName(status)}`}>
          {status}
        </Badge>
      )
    },
    enableSorting: false,
  },
  {
    accessorKey: 'livemode',
    header: ({ column }) => <DataTableColumnHeader column={column} title='Mode' />,
    cell: ({ row }) =>
      row.getValue('livemode') ? (
        <Badge variant='outline'>Live</Badge>
      ) : (
        <Badge variant='secondary'>Test</Badge>
      ),
    enableSorting: false,
    meta: { className: 'hidden md:table-cell' },
  },
  {
    accessorKey: 'error',
    header: ({ column }) => <DataTableColumnHeader column={column} title='Error' />,
    cell: ({ row }) => {
      const error = row.getValue('error') as string | null
      return error ? (
        <LongText className='max-w-72 text-red-600 dark:text-red-400'>{error}</LongText>
      ) : (
        <span className='text-muted-foreground'>-</span>
      )
    },
    enableSorting: false,
  },
  {
    accessorKey: 'provider_event_id',
    header: ({ column }) => <DataTableColumnHeader column={column} title='Event ID' />,
    cell: ({ row }) => (
      <span className='font-mono text-xs text-muted-foreground'>
        {row.getValue('provider_event_id')}
      </span>
    ),
    meta: { className: 'hidden lg:table-cell' },
    enableSorting: false,
  },
  {
    accessorKey: 'created_at',
    header: ({ column }) => <DataTableColumnHeader column={column} title='Received' />,
    cell: ({ row }) => (
      <div className='text-nowrap'>{new Date(row.getValue('created_at')).toLocaleString()}</div>
    ),
  },
]
