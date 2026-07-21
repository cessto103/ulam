'use client'

import { useState } from 'react'
import { AlertTriangle } from 'lucide-react'
import { toast } from 'sonner'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { type Recipe } from '../data/schema'
import { useDeleteRecipe } from '../hooks/use-recipes'

type RecipeDeleteDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: Recipe
  /** Fires only on a real successful delete -- unlike onOpenChange(false),
   * which also fires on a plain Cancel. */
  onDeleted?: () => void
}

export function RecipesDeleteDialog({
  open,
  onOpenChange,
  currentRow,
  onDeleted,
}: RecipeDeleteDialogProps) {
  const [value, setValue] = useState('')
  const { mutate: deleteRecipe, isPending } = useDeleteRecipe()

  const handleDelete = () => {
    if (value.trim() !== currentRow.title) return

    deleteRecipe(currentRow.id, {
      onSuccess: () => {
        onOpenChange(false)
        onDeleted?.()
        toast.success(`Deleted ${currentRow.title}.`)
      },
      onError: (error: any) => {
        toast.error(error?.response?.data?.message ?? 'Could not delete recipe.')
      },
    })
  }

  return (
    <ConfirmDialog
      open={open}
      onOpenChange={onOpenChange}
      form='recipes-delete-form'
      disabled={value.trim() !== currentRow.title || isPending}
      title={
        <span className='text-destructive'>
          <AlertTriangle
            className='me-1 inline-block stroke-destructive'
            size={18}
          />{' '}
          Delete Recipe
        </span>
      }
      desc={
        <form
          id='recipes-delete-form'
          onSubmit={(e) => {
            e.preventDefault()
            handleDelete()
          }}
          className='space-y-4'
        >
          <p className='mb-2'>
            Are you sure you want to delete{' '}
            <span className='font-bold'>{currentRow.title}</span>?
            <br />
            This action will permanently remove this recipe and its
            ingredients from the system. This cannot be undone.
          </p>

          <Label className='my-2'>
            Title:
            <Input
              value={value}
              onChange={(e) => setValue(e.target.value)}
              placeholder='Enter the recipe title to confirm deletion.'
              autoFocus
            />
          </Label>

          <Alert variant='destructive'>
            <AlertTitle>Warning!</AlertTitle>
            <AlertDescription>
              Please be careful, this operation can not be rolled back.
            </AlertDescription>
          </Alert>
        </form>
      }
      confirmText='Delete'
      destructive
    />
  )
}
