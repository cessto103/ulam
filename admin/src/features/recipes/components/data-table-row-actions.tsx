import { DotsHorizontalIcon } from '@radix-ui/react-icons'
import { type Row } from '@tanstack/react-table'
import { ChefHat, Eye, EyeOff, Trash2 } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuShortcut,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { type Recipe } from '../data/schema'
import { useTogglePublishRecipe } from '../hooks/use-recipes'
import { useRecipes } from './recipes-provider'

type DataTableRowActionsProps = {
  row: Row<Recipe>
}

export function DataTableRowActions({ row }: DataTableRowActionsProps) {
  const { setOpen, setCurrentRow } = useRecipes()
  const { mutate: togglePublish } = useTogglePublishRecipe()
  const recipe = row.original

  const handleTogglePublish = () => {
    togglePublish(
      { id: recipe.id, is_published: !recipe.is_published },
      {
        onSuccess: () => {
          toast.success(
            recipe.is_published ? 'Recipe unpublished.' : 'Recipe published.'
          )
        },
        onError: (error: any) => {
          toast.error(
            error?.response?.data?.message ?? 'Could not update recipe.'
          )
        },
      }
    )
  }

  return (
    <DropdownMenu modal={false}>
      <DropdownMenuTrigger asChild>
        <Button
          variant='ghost'
          className='flex h-8 w-8 p-0 data-[state=open]:bg-muted'
        >
          <DotsHorizontalIcon className='h-4 w-4' />
          <span className='sr-only'>Open menu</span>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align='end' className='w-44'>
        <DropdownMenuItem
          onClick={() => {
            setCurrentRow(recipe)
            setOpen('edit')
          }}
        >
          Edit
          <DropdownMenuShortcut>
            <ChefHat size={16} />
          </DropdownMenuShortcut>
        </DropdownMenuItem>
        <DropdownMenuSeparator />
        <DropdownMenuItem onClick={handleTogglePublish}>
          {recipe.is_published ? 'Unpublish' : 'Publish'}
          <DropdownMenuShortcut>
            {recipe.is_published ? <EyeOff size={16} /> : <Eye size={16} />}
          </DropdownMenuShortcut>
        </DropdownMenuItem>
        <DropdownMenuSeparator />
        <DropdownMenuItem
          onClick={() => {
            setCurrentRow(recipe)
            setOpen('delete')
          }}
          className='text-red-500!'
        >
          Delete
          <DropdownMenuShortcut>
            <Trash2 size={16} />
          </DropdownMenuShortcut>
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  )
}
