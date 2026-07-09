import React, { useState } from 'react'
import useDialogState from '@/hooks/use-dialog-state'
import { type MarketPrice } from '../data/schema'

type MarketPricesDialogType = 'add' | 'edit' | 'delete'

type MarketPricesContextType = {
  open: MarketPricesDialogType | null
  setOpen: (str: MarketPricesDialogType | null) => void
  currentRow: MarketPrice | null
  setCurrentRow: React.Dispatch<React.SetStateAction<MarketPrice | null>>
}

const MarketPricesContext =
  React.createContext<MarketPricesContextType | null>(null)

export function MarketPricesProvider({
  children,
}: {
  children: React.ReactNode
}) {
  const [open, setOpen] = useDialogState<MarketPricesDialogType>(null)
  const [currentRow, setCurrentRow] = useState<MarketPrice | null>(null)

  return (
    <MarketPricesContext value={{ open, setOpen, currentRow, setCurrentRow }}>
      {children}
    </MarketPricesContext>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export const useMarketPrices = () => {
  const marketPricesContext = React.useContext(MarketPricesContext)

  if (!marketPricesContext) {
    throw new Error(
      'useMarketPrices has to be used within <MarketPricesContext>'
    )
  }

  return marketPricesContext
}
