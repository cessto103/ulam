import React, { useState } from 'react'
import useDialogState from '@/hooks/use-dialog-state'
import { type ContentReport } from '../data/schema'

type ContentReportsDialogType = 'warn' | 'restrict' | 'ban' | 'delete'

type ContentReportsContextType = {
  open: ContentReportsDialogType | null
  setOpen: (str: ContentReportsDialogType | null) => void
  currentRow: ContentReport | null
  setCurrentRow: React.Dispatch<React.SetStateAction<ContentReport | null>>
}

const ContentReportsContext =
  React.createContext<ContentReportsContextType | null>(null)

export function ContentReportsProvider({
  children,
}: {
  children: React.ReactNode
}) {
  const [open, setOpen] = useDialogState<ContentReportsDialogType>(null)
  const [currentRow, setCurrentRow] = useState<ContentReport | null>(null)

  return (
    <ContentReportsContext value={{ open, setOpen, currentRow, setCurrentRow }}>
      {children}
    </ContentReportsContext>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export const useContentReports = () => {
  const context = React.useContext(ContentReportsContext)

  if (!context) {
    throw new Error(
      'useContentReports has to be used within <ContentReportsContext>'
    )
  }

  return context
}
