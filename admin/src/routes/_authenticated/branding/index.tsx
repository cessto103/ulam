import { createFileRoute } from '@tanstack/react-router'
import { BrandingPage } from '@/features/branding'

export const Route = createFileRoute('/_authenticated/branding/')({
  component: BrandingPage,
})
