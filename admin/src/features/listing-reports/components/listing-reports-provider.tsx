import React, { useState } from 'react'
import useDialogState from '@/hooks/use-dialog-state'
import { type ListingReport } from '../data/schema'

type ListingReportsDialogType = 'delete' | 'ban-owner' | 'deactivate'

type ListingReportsContextType = {
  open: ListingReportsDialogType | null
  setOpen: (str: ListingReportsDialogType | null) => void
  currentRow: ListingReport | null
  setCurrentRow: React.Dispatch<React.SetStateAction<ListingReport | null>>
}

const ListingReportsContext =
  React.createContext<ListingReportsContextType | null>(null)

export function ListingReportsProvider({
  children,
}: {
  children: React.ReactNode
}) {
  const [open, setOpen] = useDialogState<ListingReportsDialogType>(null)
  const [currentRow, setCurrentRow] = useState<ListingReport | null>(null)

  return (
    <ListingReportsContext value={{ open, setOpen, currentRow, setCurrentRow }}>
      {children}
    </ListingReportsContext>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export const useListingReports = () => {
  const context = React.useContext(ListingReportsContext)

  if (!context) {
    throw new Error(
      'useListingReports has to be used within <ListingReportsContext>'
    )
  }

  return context
}
