import { type Row } from '@tanstack/react-table'
import { Trash2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { type PostComment } from '../data/schema'
import { useComments } from './comments-provider'

type DataTableRowActionsProps = {
  row: Row<PostComment>
}

export function DataTableRowActions({ row }: DataTableRowActionsProps) {
  const { setOpen, setCurrentRow } = useComments()

  return (
    <Button
      variant='ghost'
      className='flex h-8 w-8 p-0 text-red-500! hover:text-red-500!'
      aria-label='Delete comment'
      title='Delete comment'
      onClick={() => {
        setCurrentRow(row.original)
        setOpen('delete')
      }}
    >
      <Trash2 className='h-4 w-4' />
      <span className='sr-only'>Delete comment</span>
    </Button>
  )
}
