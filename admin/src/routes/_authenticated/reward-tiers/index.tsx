import { createFileRoute } from '@tanstack/react-router'
import { RewardTiers } from '@/features/reward-tiers'

export const Route = createFileRoute('/_authenticated/reward-tiers/')({
  component: RewardTiers,
})
