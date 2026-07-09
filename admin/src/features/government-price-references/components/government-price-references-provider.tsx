import React, { useState } from 'react'
import useDialogState from '@/hooks/use-dialog-state'
import { type GovernmentPriceReference } from '../data/schema'

type GovernmentPriceReferencesDialogType = 'add' | 'edit' | 'delete'

type GovernmentPriceReferencesContextType = {
  open: GovernmentPriceReferencesDialogType | null
  setOpen: (str: GovernmentPriceReferencesDialogType | null) => void
  currentRow: GovernmentPriceReference | null
  setCurrentRow: React.Dispatch<
    React.SetStateAction<GovernmentPriceReference | null>
  >
}

const GovernmentPriceReferencesContext =
  React.createContext<GovernmentPriceReferencesContextType | null>(null)

export function GovernmentPriceReferencesProvider({
  children,
}: {
  children: React.ReactNode
}) {
  const [open, setOpen] =
    useDialogState<GovernmentPriceReferencesDialogType>(null)
  const [currentRow, setCurrentRow] =
    useState<GovernmentPriceReference | null>(null)

  return (
    <GovernmentPriceReferencesContext
      value={{ open, setOpen, currentRow, setCurrentRow }}
    >
      {children}
    </GovernmentPriceReferencesContext>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export const useGovernmentPriceReferences = () => {
  const context = React.useContext(GovernmentPriceReferencesContext)

  if (!context) {
    throw new Error(
      'useGovernmentPriceReferences has to be used within <GovernmentPriceReferencesContext>'
    )
  }

  return context
}
