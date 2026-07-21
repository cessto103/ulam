import React, { useState } from 'react'
import useDialogState from '@/hooks/use-dialog-state'
import { type Invoice } from '../data/schema'

type InvoicesDialogType = 'add' | 'edit' | 'view' | 'delete' | 'mark-paid' | 'void' | 'email'

type InvoicesContextType = {
  open: InvoicesDialogType | null
  setOpen: (str: InvoicesDialogType | null) => void
  currentRow: Invoice | null
  setCurrentRow: React.Dispatch<React.SetStateAction<Invoice | null>>
}

const InvoicesContext = React.createContext<InvoicesContextType | null>(null)

export function InvoicesProvider({ children }: { children: React.ReactNode }) {
  const [open, setOpen] = useDialogState<InvoicesDialogType>(null)
  const [currentRow, setCurrentRow] = useState<Invoice | null>(null)

  return (
    <InvoicesContext value={{ open, setOpen, currentRow, setCurrentRow }}>
      {children}
    </InvoicesContext>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export const useInvoices = () => {
  const invoicesContext = React.useContext(InvoicesContext)

  if (!invoicesContext) {
    throw new Error('useInvoices has to be used within <InvoicesContext>')
  }

  return invoicesContext
}
