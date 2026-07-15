import { type ColumnDef } from '@tanstack/react-table'
import { cn } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import { DataTableColumnHeader } from '@/components/data-table'
import { LongText } from '@/components/long-text'
import { sources } from '../data/data'
import { type GovernmentPriceReference } from '../data/schema'
import { DataTableRowActions } from './data-table-row-actions'

export const governmentPriceReferencesColumns: ColumnDef<GovernmentPriceReference>[] =
  [
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
      accessorKey: 'source',
      header: ({ column }) => (
        <DataTableColumnHeader column={column} title='Source' />
      ),
      cell: ({ row }) => {
        const source = row.getValue('source') as string
        const sourceInfo = sources.find((s) => s.value === source)
        return (
          <Badge
            variant={source === 'da_bantay_presyo' ? 'default' : 'outline'}
          >
            {sourceInfo?.label ?? source}
          </Badge>
        )
      },
      enableSorting: false,
    },
    {
      accessorKey: 'item_name',
      header: ({ column }) => (
        <DataTableColumnHeader column={column} title='Item' />
      ),
      cell: ({ row }) => (
        <LongText className='max-w-36 ps-3'>
          {row.getValue('item_name')}
        </LongText>
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
      accessorKey: 'category',
      header: ({ column }) => (
        <DataTableColumnHeader column={column} title='Category' />
      ),
      cell: ({ row }) => <div>{row.getValue('category') ?? '-'}</div>,
      meta: { className: 'hidden md:table-cell' },
      enableSorting: false,
    },
    {
      accessorKey: 'price_min',
      header: ({ column }) => (
        <DataTableColumnHeader column={column} title='Min price' />
      ),
      cell: ({ row }) => (
        <div>₱{Number(row.getValue('price_min')).toFixed(2)}</div>
      ),
    },
    {
      accessorKey: 'price_max',
      header: ({ column }) => (
        <DataTableColumnHeader column={column} title='Max price' />
      ),
      cell: ({ row }) => (
        <div>₱{Number(row.getValue('price_max')).toFixed(2)}</div>
      ),
    },
    {
      accessorKey: 'unit',
      header: ({ column }) => (
        <DataTableColumnHeader column={column} title='Unit' />
      ),
      cell: ({ row }) => <div>{row.getValue('unit')}</div>,
      enableSorting: false,
    },
    {
      accessorKey: 'region',
      header: ({ column }) => (
        <DataTableColumnHeader column={column} title='Region' />
      ),
      cell: ({ row }) => <div>{row.getValue('region') ?? '-'}</div>,
      meta: { className: 'hidden lg:table-cell' },
      enableSorting: false,
    },
    {
      accessorKey: 'bulletin_date',
      header: ({ column }) => (
        <DataTableColumnHeader column={column} title='Bulletin date' />
      ),
      cell: ({ row }) => {
        const value = row.getValue('bulletin_date') as string | null
        return <div>{value ? new Date(value).toLocaleDateString() : '-'}</div>
      },
      meta: { className: 'hidden lg:table-cell' },
    },
    {
      accessorKey: 'updated_at',
      header: ({ column }) => (
        <DataTableColumnHeader column={column} title='Updated' />
      ),
      cell: ({ row }) => (
        <div>{new Date(row.getValue('updated_at')).toLocaleDateString()}</div>
      ),
      meta: { className: 'hidden lg:table-cell' },
    },
    {
      id: 'actions',
      cell: DataTableRowActions,
    },
  ]
