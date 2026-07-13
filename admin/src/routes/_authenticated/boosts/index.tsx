import { createFileRoute } from '@tanstack/react-router'
import { Boosts } from '@/features/boosts'

export const Route = createFileRoute('/_authenticated/boosts/')({
  component: Boosts,
})
