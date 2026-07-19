import { createFileRoute } from '@tanstack/react-router'
import { StaplePrices } from '@/features/staple-prices'

export const Route = createFileRoute('/_authenticated/staple-prices/')({
  component: StaplePrices,
})
