import { type ColumnDef } from '@tanstack/react-table'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { DataTableColumnHeader } from '@/components/data-table'
import { LongText } from '@/components/long-text'
import { type Payment } from '../data/schema'

/** Base columns shared by every render of the table. */
const baseColumns: ColumnDef<Payment>[] = [
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
    cell: ({ row }) => {
      const status = row.getValue('status') as string
      const className =
        status === 'refunded' || status === 'partially_refunded'
          ? 'bg-purple-500/15 text-purple-600 dark:text-purple-400'
          : status === 'failed'
            ? 'bg-red-500/15 text-red-600 dark:text-red-400'
            : ''
      return (
        <Badge className={`capitalize ${className}`}>
          {status.replace('_', ' ')}
        </Badge>
      )
    },
    enableSorting: false,
  },
  {
    accessorKey: 'provider_payment_id',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Reference' />
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

/** Refunds only apply to PayMongo payments that are still in the 'paid' state. */
function isRefundable(payment: Payment): boolean {
  return payment.provider === 'paymongo' && payment.status === 'paid'
}

export function getPaymentsColumns(
  onRefund: (payment: Payment) => void
): ColumnDef<Payment>[] {
  return [
    ...baseColumns,
    {
      id: 'actions',
      header: () => <span className='sr-only'>Actions</span>,
      cell: ({ row }) =>
        isRefundable(row.original) ? (
          <Button
            size='sm'
            variant='outline'
            className='h-7'
            onClick={() => onRefund(row.original)}
          >
            Refund
          </Button>
        ) : null,
      enableSorting: false,
      enableHiding: false,
    },
  ]
}

// Kept for any other caller expecting the static export.
export const paymentsColumns = baseColumns
