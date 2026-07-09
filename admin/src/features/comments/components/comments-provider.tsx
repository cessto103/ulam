import React, { useState } from 'react'
import useDialogState from '@/hooks/use-dialog-state'
import { type PostComment } from '../data/schema'

type CommentsDialogType = 'delete'

type CommentsContextType = {
  open: CommentsDialogType | null
  setOpen: (str: CommentsDialogType | null) => void
  currentRow: PostComment | null
  setCurrentRow: React.Dispatch<React.SetStateAction<PostComment | null>>
}

const CommentsContext = React.createContext<CommentsContextType | null>(null)

export function CommentsProvider({ children }: { children: React.ReactNode }) {
  const [open, setOpen] = useDialogState<CommentsDialogType>(null)
  const [currentRow, setCurrentRow] = useState<PostComment | null>(null)

  return (
    <CommentsContext value={{ open, setOpen, currentRow, setCurrentRow }}>
      {children}
    </CommentsContext>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export const useComments = () => {
  const commentsContext = React.useContext(CommentsContext)

  if (!commentsContext) {
    throw new Error('useComments has to be used within <CommentsContext>')
  }

  return commentsContext
}
