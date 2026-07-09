import { Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useRecipes } from './recipes-provider'

export function RecipesPrimaryButtons() {
  const { setOpen } = useRecipes()
  return (
    <div className='flex gap-2'>
      <Button className='space-x-1' onClick={() => setOpen('add')}>
        <span>Add Recipe</span> <Plus size={18} />
      </Button>
    </div>
  )
}
