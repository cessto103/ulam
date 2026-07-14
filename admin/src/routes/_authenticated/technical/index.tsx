import { createFileRoute } from '@tanstack/react-router'
import { TechnicalGuide } from '@/features/technical'

export const Route = createFileRoute('/_authenticated/technical/')({
  component: TechnicalGuide,
})
