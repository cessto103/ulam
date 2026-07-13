import { createFileRoute } from '@tanstack/react-router'
import { Monetization } from '@/features/monetization'

export const Route = createFileRoute('/_authenticated/monetization/')({
  component: Monetization,
})
