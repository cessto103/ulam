import { createFileRoute } from '@tanstack/react-router'
import { Faqs } from '@/features/faqs'

export const Route = createFileRoute('/_authenticated/faqs/')({
  component: Faqs,
})
