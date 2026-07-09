import React, { useState } from 'react'
import useDialogState from '@/hooks/use-dialog-state'
import { type Tindahan } from '../data/schema'

type TindahanDialogType = 'add' | 'edit' | 'delete'

type TindahanContextType = {
  open: TindahanDialogType | null
  setOpen: (str: TindahanDialogType | null) => void
  currentRow: Tindahan | null
  setCurrentRow: React.Dispatch<React.SetStateAction<Tindahan | null>>
}

const TindahanContext = React.createContext<TindahanContextType | null>(null)

export function TindahanProvider({ children }: { children: React.ReactNode }) {
  const [open, setOpen] = useDialogState<TindahanDialogType>(null)
  const [currentRow, setCurrentRow] = useState<Tindahan | null>(null)

  return (
    <TindahanContext value={{ open, setOpen, currentRow, setCurrentRow }}>
      {children}
    </TindahanContext>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export const useTindahan = () => {
  const tindahanContext = React.useContext(TindahanContext)

  if (!tindahanContext) {
    throw new Error('useTindahan has to be used within <TindahanContext>')
  }

  return tindahanContext
}
