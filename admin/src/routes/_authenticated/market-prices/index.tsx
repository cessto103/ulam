import z from 'zod'
import { createFileRoute } from '@tanstack/react-router'
import { MarketPrices } from '@/features/market-prices'

const marketPricesSearchSchema = z.object({
  page: z.number().optional().catch(1),
  pageSize: z.number().optional().catch(10),
  search: z.string().optional().catch(''),
  market_id: z.number().optional().catch(undefined),
  tindahan_id: z.number().optional().catch(undefined),
  category: z.array(z.string()).optional().catch([]),
  is_available: z
    .array(z.enum(['available', 'unavailable']))
    .optional()
    .catch([]),
})

export const Route = createFileRoute('/_authenticated/market-prices/')({
  validateSearch: marketPricesSearchSchema,
  component: MarketPrices,
})
