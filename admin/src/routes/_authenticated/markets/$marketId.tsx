import { createFileRoute } from '@tanstack/react-router'
import { MarketDetail } from '@/features/markets/market-detail'

export const Route = createFileRoute('/_authenticated/markets/$marketId')({
  component: RouteComponent,
})

function RouteComponent() {
  const { marketId } = Route.useParams()
  return <MarketDetail marketId={Number(marketId)} />
}
