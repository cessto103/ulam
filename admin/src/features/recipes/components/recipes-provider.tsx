import React, { useState } from 'react'
import useDialogState from '@/hooks/use-dialog-state'
import { type Recipe } from '../data/schema'

type RecipesDialogType = 'add' | 'edit' | 'delete'

type RecipesContextType = {
  open: RecipesDialogType | null
  setOpen: (str: RecipesDialogType | null) => void
  currentRow: Recipe | null
  setCurrentRow: React.Dispatch<React.SetStateAction<Recipe | null>>
}

const RecipesContext = React.createContext<RecipesContextType | null>(null)

export function RecipesProvider({ children }: { children: React.ReactNode }) {
  const [open, setOpen] = useDialogState<RecipesDialogType>(null)
  const [currentRow, setCurrentRow] = useState<Recipe | null>(null)

  return (
    <RecipesContext value={{ open, setOpen, currentRow, setCurrentRow }}>
      {children}
    </RecipesContext>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export const useRecipes = () => {
  const recipesContext = React.useContext(RecipesContext)

  if (!recipesContext) {
    throw new Error('useRecipes has to be used within <RecipesContext>')
  }

  return recipesContext
}
