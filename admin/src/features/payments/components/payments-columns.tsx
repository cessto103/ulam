import { type ColumnDef } from '@tanstack/react-table'
import { Badge } from '@/components/ui/badge'
import { DataTableColumnHeader } from '@/components/data-table'
import { LongText } from '@/components/long-text'
import { type Payment } from '../data/schema'

export const paymentsColumns: ColumnDef<Payment>[] = [
  {
    accessorKey: 'id',
    header: ({ column }) => <DataTableColumnHeader column={column} title='#' />,
    cell: ({ row }) => <div className='ps-3'>{row.getValue('id')}</div>,
    enableHiding: false,
  },
  {
    id: 'user',
    accessorFn: (row) => row.user?.name ?? '(deleted user)',
    header: ({ column }) => <DataTableColumnHeader column={column} title='User' />,
    cell: ({ row }) => (
      <div>
        <LongText className='max-w-40'>
          {row.original.user?.name ?? '(deleted user)'}
        </LongText>
        {row.original.user && (
          <p className='text-xs text-muted-foreground'>
            {row.original.user.email}
          </p>
        )}
      </div>
    ),
    enableSorting: false,
  },
  {
    accessorKey: 'plan_type',
    header: ({ column }) => <DataTableColumnHeader column={column} title='Plan' />,
    cell: ({ row }) => (
      <Badge variant='outline' className='capitalize'>
        {row.getValue('plan_type')}
      </Badge>
    ),
    enableSorting: false,
  },
  {
    accessorKey: 'amount',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Amount' />
    ),
    cell: ({ row }) => (
      <div className='font-medium'>
        ₱{((row.getValue('amount') as number) / 100).toFixed(2)}
      </div>
    ),
    enableSorting: false,
  },
  {
    accessorKey: 'status',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Status' />
    ),
    cell: ({ row }) => (
      <Badge className='capitalize'>{row.getValue('status')}</Badge>
    ),
    enableSorting: false,
  },
  {
    accessorKey: 'provider_payment_id',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='PayMongo Ref' />
    ),
    cell: ({ row }) => (
      <span className='font-mono text-xs'>
        {row.getValue('provider_payment_id') ?? '—'}
      </span>
    ),
    meta: { className: 'hidden lg:table-cell' },
    enableSorting: false,
  },
  {
    accessorKey: 'paid_at',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Paid at' />
    ),
    cell: ({ row }) => (
      <div>{new Date(row.getValue('paid_at')).toLocaleString()}</div>
    ),
  },
]
