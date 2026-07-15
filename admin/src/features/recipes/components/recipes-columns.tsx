import { type ColumnDef } from '@tanstack/react-table'
import { CheckCircle2, Crown, XCircle } from 'lucide-react'
import { cn } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import { DataTableColumnHeader } from '@/components/data-table'
import { LongText } from '@/components/long-text'
import { budgetTags, sources } from '../data/data'
import { type Recipe } from '../data/schema'
import { DataTableRowActions } from './data-table-row-actions'

export const recipesColumns: ColumnDef<Recipe>[] = [
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
    accessorKey: 'title',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Title' />
    ),
    cell: ({ row }) => (
      <LongText className='max-w-48 ps-3'>{row.getValue('title')}</LongText>
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
  },
  {
    accessorKey: 'source',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Source' />
    ),
    cell: ({ row }) => {
      const value = row.getValue('source') as string
      const label = sources.find((s) => s.value === value)?.label ?? value
      return (
        <Badge variant='outline' className='capitalize'>
          {label}
        </Badge>
      )
    },
    enableSorting: false,
  },
  {
    accessorKey: 'budget_tag',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Budget' />
    ),
    cell: ({ row }) => {
      const value = row.getValue('budget_tag') as string
      const label = budgetTags.find((b) => b.value === value)?.label ?? value
      return <Badge variant='secondary'>{label}</Badge>
    },
    enableSorting: false,
  },
  {
    accessorKey: 'estimated_cost',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Est. Cost' />
    ),
    cell: ({ row }) => {
      const value = row.getValue('estimated_cost') as string | null
      return <div>{value ? `₱${Number(value).toFixed(2)}` : '-'}</div>
    },
    meta: { className: 'hidden lg:table-cell' },
  },
  {
    accessorKey: 'servings',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Servings' />
    ),
    cell: ({ row }) => <div>{row.getValue('servings') ?? '-'}</div>,
    meta: { className: 'hidden lg:table-cell' },
  },
  {
    accessorKey: 'difficulty',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Difficulty' />
    ),
    cell: ({ row }) => (
      <div className='capitalize'>{row.getValue('difficulty') ?? '-'}</div>
    ),
    meta: { className: 'hidden lg:table-cell' },
  },
  {
    accessorKey: 'is_published',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Published' />
    ),
    cell: ({ row }) =>
      row.getValue('is_published') ? (
        <CheckCircle2 size={16} className='text-primary' />
      ) : (
        <XCircle size={16} className='text-muted-foreground' />
      ),
    enableSorting: false,
  },
  {
    accessorKey: 'is_premium_only',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Premium' />
    ),
    cell: ({ row }) =>
      row.getValue('is_premium_only') ? (
        <Crown size={16} className='text-amber-500' />
      ) : (
        <span className='text-muted-foreground'>-</span>
      ),
    enableSorting: false,
    meta: { className: 'hidden md:table-cell' },
  },
  {
    accessorKey: 'average_rating',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Rating' />
    ),
    cell: ({ row }) => (
      <div>{Number(row.getValue('average_rating')).toFixed(1)}</div>
    ),
    meta: { className: 'hidden lg:table-cell' },
  },
  {
    accessorKey: 'save_count',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Saves' />
    ),
    cell: ({ row }) => <div>{row.getValue('save_count')}</div>,
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
    meta: { className: 'hidden md:table-cell' },
  },
  {
    id: 'actions',
    cell: DataTableRowActions,
  },
]
