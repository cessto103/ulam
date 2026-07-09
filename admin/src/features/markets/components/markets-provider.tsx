import React, { useState } from 'react'
import useDialogState from '@/hooks/use-dialog-state'
import { type Market } from '../data/schema'

type MarketsDialogType = 'add' | 'edit' | 'delete' | 'refresh-ai'

type MarketsContextType = {
  open: MarketsDialogType | null
  setOpen: (str: MarketsDialogType | null) => void
  currentRow: Market | null
  setCurrentRow: React.Dispatch<React.SetStateAction<Market | null>>
}

const MarketsContext = React.createContext<MarketsContextType | null>(null)

export function MarketsProvider({ children }: { children: React.ReactNode }) {
  const [open, setOpen] = useDialogState<MarketsDialogType>(null)
  const [currentRow, setCurrentRow] = useState<Market | null>(null)

  return (
    <MarketsContext value={{ open, setOpen, currentRow, setCurrentRow }}>
      {children}
    </MarketsContext>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export const useMarkets = () => {
  const marketsContext = React.useContext(MarketsContext)

  if (!marketsContext) {
    throw new Error('useMarkets has to be used within <MarketsContext>')
  }

  return marketsContext
}
