'use client'

import { useState } from 'react'
import { Trash2 } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  useCreateRecipeIngredient,
  useDeleteRecipeIngredient,
  useRecipeIngredientsQuery,
} from '../hooks/use-recipe-ingredients'

type RecipeIngredientsEditorProps = {
  recipeId: number
}

const emptyForm = {
  name: '',
  quantity: '',
  unit: '',
  estimated_price: '',
  sort_order: '',
}

export function RecipeIngredientsEditor({
  recipeId,
}: RecipeIngredientsEditorProps) {
  const { data: ingredients, isLoading } = useRecipeIngredientsQuery(recipeId)
  const { mutate: createIngredient, isPending: creating } =
    useCreateRecipeIngredient(recipeId)
  const { mutate: deleteIngredient } = useDeleteRecipeIngredient(recipeId)
  const [form, setForm] = useState(emptyForm)

  const handleAdd = () => {
    if (!form.name.trim()) {
      toast.error('Ingredient name is required.')
      return
    }
    createIngredient(
      {
        name: form.name.trim(),
        quantity: form.quantity.trim() || null,
        unit: form.unit.trim() || null,
        estimated_price: form.estimated_price
          ? Number(form.estimated_price)
          : null,
        sort_order: form.sort_order ? Number(form.sort_order) : undefined,
      },
      {
        onSuccess: () => {
          setForm(emptyForm)
          toast.success('Ingredient added.')
        },
        onError: (error: any) => {
          toast.error(
            error?.response?.data?.message ?? 'Could not add ingredient.'
          )
        },
      }
    )
  }

  const handleDelete = (id: number) => {
    deleteIngredient(id, {
      onSuccess: () => toast.success('Ingredient removed.'),
      onError: (error: any) => {
        toast.error(
          error?.response?.data?.message ?? 'Could not remove ingredient.'
        )
      },
    })
  }

  return (
    <div className='space-y-3'>
      <div className='overflow-hidden rounded-md border'>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Name</TableHead>
              <TableHead>Quantity</TableHead>
              <TableHead>Unit</TableHead>
              <TableHead>Est. Price</TableHead>
              <TableHead>Sort</TableHead>
              <TableHead className='w-10' />
            </TableRow>
          </TableHeader>
          <TableBody>
            {isLoading ? (
              <TableRow>
                <TableCell colSpan={6} className='h-16 text-center'>
                  Loading...
                </TableCell>
              </TableRow>
            ) : ingredients && ingredients.length > 0 ? (
              ingredients.map((ingredient) => (
                <TableRow key={ingredient.id}>
                  <TableCell>{ingredient.name}</TableCell>
                  <TableCell>{ingredient.quantity ?? '—'}</TableCell>
                  <TableCell>{ingredient.unit ?? '—'}</TableCell>
                  <TableCell>
                    {ingredient.estimated_price
                      ? `₱${Number(ingredient.estimated_price).toFixed(2)}`
                      : '—'}
                  </TableCell>
                  <TableCell>{ingredient.sort_order}</TableCell>
                  <TableCell>
                    <Button
                      type='button'
                      variant='ghost'
                      size='icon'
                      onClick={() => handleDelete(ingredient.id)}
                      aria-label='Remove ingredient'
                    >
                      <Trash2 size={16} />
                    </Button>
                  </TableCell>
                </TableRow>
              ))
            ) : (
              <TableRow>
                <TableCell
                  colSpan={6}
                  className='h-16 text-center text-muted-foreground'
                >
                  No ingredients yet.
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>

      <div className='grid grid-cols-12 gap-2'>
        <Input
          placeholder='Name'
          className='col-span-4'
          value={form.name}
          onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
        />
        <Input
          placeholder='Qty'
          className='col-span-2'
          value={form.quantity}
          onChange={(e) =>
            setForm((f) => ({ ...f, quantity: e.target.value }))
          }
        />
        <Input
          placeholder='Unit'
          className='col-span-2'
          value={form.unit}
          onChange={(e) => setForm((f) => ({ ...f, unit: e.target.value }))}
        />
        <Input
          placeholder='Est. price'
          type='number'
          className='col-span-2'
          value={form.estimated_price}
          onChange={(e) =>
            setForm((f) => ({ ...f, estimated_price: e.target.value }))
          }
        />
        <Input
          placeholder='Sort'
          type='number'
          className='col-span-1'
          value={form.sort_order}
          onChange={(e) =>
            setForm((f) => ({ ...f, sort_order: e.target.value }))
          }
        />
        <Button
          type='button'
          className='col-span-1'
          onClick={handleAdd}
          disabled={creating}
        >
          Add
        </Button>
      </div>
    </div>
  )
}
