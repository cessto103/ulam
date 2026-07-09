import { z } from 'zod'

const relatedMarketSchema = z.object({
  id: z.number(),
  name: z.string(),
})

const relatedTindahanSchema = z.object({
  id: z.number(),
  name: z.string(),
})

const marketPriceSchema = z.object({
  id: z.number(),
  market_id: z.number().nullable(),
  market: relatedMarketSchema.nullable().optional(),
  tindahan_id: z.number().nullable(),
  tindahan: relatedTindahanSchema.nullable().optional(),
  item_name: z.string(),
  category: z.string().nullable(),
  price_per_unit: z.string(),
  unit: z.string(),
  is_available: z.boolean(),
  created_at: z.string(),
  updated_at: z.string(),
})
export type MarketPrice = z.infer<typeof marketPriceSchema>
