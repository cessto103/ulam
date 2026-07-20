import React, { useState } from 'react'
import useDialogState from '@/hooks/use-dialog-state'
import { type SponsoredAd } from '../data/schema'

type SponsoredAdsDialogType = 'add' | 'edit' | 'delete'

type SponsoredAdsContextType = {
  open: SponsoredAdsDialogType | null
  setOpen: (str: SponsoredAdsDialogType | null) => void
  currentRow: SponsoredAd | null
  setCurrentRow: React.Dispatch<React.SetStateAction<SponsoredAd | null>>
}

const SponsoredAdsContext = React.createContext<SponsoredAdsContextType | null>(null)

export function SponsoredAdsProvider({ children }: { children: React.ReactNode }) {
  const [open, setOpen] = useDialogState<SponsoredAdsDialogType>(null)
  const [currentRow, setCurrentRow] = useState<SponsoredAd | null>(null)

  return (
    <SponsoredAdsContext value={{ open, setOpen, currentRow, setCurrentRow }}>
      {children}
    </SponsoredAdsContext>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export const useSponsoredAds = () => {
  const sponsoredAdsContext = React.useContext(SponsoredAdsContext)

  if (!sponsoredAdsContext) {
    throw new Error('useSponsoredAds has to be used within <SponsoredAdsContext>')
  }

  return sponsoredAdsContext
}
