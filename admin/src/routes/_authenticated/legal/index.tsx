import { createFileRoute } from '@tanstack/react-router'
import { LegalContent } from '@/features/legal'

export const Route = createFileRoute('/_authenticated/legal/')({
  component: LegalContent,
})
