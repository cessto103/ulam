import { createFileRoute } from '@tanstack/react-router'
import { PremiumSubscribers } from '@/features/premium-subscribers'

export const Route = createFileRoute('/_authenticated/premium-subscribers/')({
  component: PremiumSubscribers,
})
