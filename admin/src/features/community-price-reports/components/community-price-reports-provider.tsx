import React, { useState } from 'react'
import useDialogState from '@/hooks/use-dialog-state'
import { type CommunityPriceReport } from '../data/schema'

type CommunityPriceReportsDialogType = 'add' | 'edit' | 'delete'

type CommunityPriceReportsContextType = {
  open: CommunityPriceReportsDialogType | null
  setOpen: (str: CommunityPriceReportsDialogType | null) => void
  currentRow: CommunityPriceReport | null
  setCurrentRow: React.Dispatch<
    React.SetStateAction<CommunityPriceReport | null>
  >
}

const CommunityPriceReportsContext =
  React.createContext<CommunityPriceReportsContextType | null>(null)

export function CommunityPriceReportsProvider({
  children,
}: {
  children: React.ReactNode
}) {
  const [open, setOpen] = useDialogState<CommunityPriceReportsDialogType>(null)
  const [currentRow, setCurrentRow] = useState<CommunityPriceReport | null>(
    null
  )

  return (
    <CommunityPriceReportsContext
      value={{ open, setOpen, currentRow, setCurrentRow }}
    >
      {children}
    </CommunityPriceReportsContext>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export const useCommunityPriceReports = () => {
  const context = React.useContext(CommunityPriceReportsContext)

  if (!context) {
    throw new Error(
      'useCommunityPriceReports has to be used within <CommunityPriceReportsContext>'
    )
  }

  return context
}
