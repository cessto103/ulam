import { type ColumnDef } from '@tanstack/react-table'
import { Ban, CheckCircle2 } from 'lucide-react'
import { cn } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import { DataTableColumnHeader } from '@/components/data-table'
import { LongText } from '@/components/long-text'
import { roles } from '../data/data'
import { type User } from '../data/schema'
import { DataTableRowActions } from './data-table-row-actions'

export const usersColumns: ColumnDef<User>[] = [
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
      <LongText className='max-w-36 ps-3'>{row.getValue('name')}</LongText>
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
    accessorKey: 'username',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Username' />
    ),
    cell: ({ row }) => <div>@{row.getValue('username')}</div>,
  },
  {
    accessorKey: 'email',
    header: ({ column }) => <DataTableColumnHeader column={column} title='Email' />,
    cell: ({ row }) => (
      <div className='w-fit text-nowrap'>{row.getValue('email')}</div>
    ),
  },
  {
    accessorKey: 'municipality',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Municipality' />
    ),
    cell: ({ row }) => <div>{row.getValue('municipality') ?? '—'}</div>,
    meta: { className: 'hidden md:table-cell' },
  },
  {
    accessorKey: 'plan',
    header: ({ column }) => <DataTableColumnHeader column={column} title='Plan' />,
    cell: ({ row }) => {
      const plan = row.getValue('plan') as string
      return (
        <Badge variant={plan === 'premium' ? 'default' : 'outline'} className='capitalize'>
          {plan}
        </Badge>
      )
    },
    enableSorting: false,
  },
  {
    accessorKey: 'role',
    header: ({ column }) => <DataTableColumnHeader column={column} title='Role' />,
    cell: ({ row }) => {
      const role = row.getValue('role') as string
      const roleInfo = roles.find((r) => r.value === role)
      return (
        <div className='flex items-center gap-x-2'>
          {roleInfo?.icon && (
            <roleInfo.icon size={16} className='text-muted-foreground' />
          )}
          <span className='text-sm capitalize'>{role}</span>
        </div>
      )
    },
    enableSorting: false,
  },
  {
    accessorKey: 'xp',
    header: ({ column }) => <DataTableColumnHeader column={column} title='XP' />,
    cell: ({ row }) => <div>{row.getValue('xp')}</div>,
    meta: { className: 'hidden lg:table-cell' },
  },
  {
    id: 'banned',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Status' />
    ),
    cell: ({ row }) =>
      row.original.banned_at ? (
        <Badge variant='destructive' className='gap-1'>
          <Ban size={12} /> Banned
        </Badge>
      ) : (
        <CheckCircle2 size={16} className='text-muted-foreground' />
      ),
    enableSorting: false,
  },
  {
    id: 'actions',
    cell: DataTableRowActions,
  },
]
