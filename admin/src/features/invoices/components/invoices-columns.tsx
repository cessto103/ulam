import { type ColumnDef } from '@tanstack/react-table'
import { Badge } from '@/components/ui/badge'
import { DataTableColumnHeader } from '@/components/data-table'
import { LongText } from '@/components/long-text'
import { type Invoice } from '../data/schema'
import { DataTableRowActions } from './data-table-row-actions'

const STATUS_VARIANT: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
  draft: 'secondary',
  issued: 'default',
  void: 'destructive',
}

export const invoicesColumns: ColumnDef<Invoice>[] = [
  {
    accessorKey: 'invoice_number',
    header: ({ column }) => <DataTableColumnHeader column={column} title='Number' />,
    cell: ({ row }) => (
      <span className='font-mono text-sm'>{row.getValue('invoice_number') ?? '—'}</span>
    ),
    enableSorting: false,
  },
  {
    accessorKey: 'buyer_name',
    header: ({ column }) => <DataTableColumnHeader column={column} title='Buyer' />,
    cell: ({ row }) => <LongText className='max-w-40'>{row.getValue('buyer_name')}</LongText>,
    enableSorting: false,
  },
  {
    accessorKey: 'description',
    header: ({ column }) => <DataTableColumnHeader column={column} title='Description' />,
    cell: ({ row }) => <LongText className='max-w-56 text-muted-foreground'>{row.getValue('description')}</LongText>,
    meta: { className: 'hidden md:table-cell' },
    enableSorting: false,
  },
  {
    accessorKey: 'amount',
    header: ({ column }) => <DataTableColumnHeader column={column} title='Amount' />,
    cell: ({ row }) => <div>₱{Number(row.getValue('amount')).toFixed(2)}</div>,
  },
  {
    accessorKey: 'status',
    header: ({ column }) => <DataTableColumnHeader column={column} title='Status' />,
    cell: ({ row }) => {
      const status = row.getValue('status') as string
      return (
        <Badge variant={STATUS_VARIANT[status] ?? 'outline'} className='capitalize'>
          {status}
        </Badge>
      )
    },
    enableSorting: false,
  },
  {
    accessorKey: 'issued_at',
    header: ({ column }) => <DataTableColumnHeader column={column} title='Issued' />,
    cell: ({ row }) => {
      const issuedAt = row.getValue('issued_at') as string | null
      return <div className='text-sm whitespace-nowrap'>{issuedAt ? new Date(issuedAt).toLocaleDateString() : '—'}</div>
    },
    meta: { className: 'hidden lg:table-cell' },
  },
  {
    id: 'actions',
    cell: DataTableRowActions,
  },
]
