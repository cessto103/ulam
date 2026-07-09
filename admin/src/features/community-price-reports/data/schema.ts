import { z } from 'zod'

const relatedNameSchema = z.object({
  id: z.number(),
  name: z.string(),
})

const communityPriceReportSchema = z.object({
  id: z.number(),
  user_id: z.number(),
  user: relatedNameSchema.nullable(),
  tindahan_id: z.number().nullable(),
  tindahan: relatedNameSchema.nullable(),
  market_id: z.number().nullable(),
  market: relatedNameSchema.nullable(),
  item_name: z.string(),
  category: z.string().nullable(),
  reported_price: z.string(),
  unit: z.string(),
  barangay: z.string().nullable(),
  municipality: z.string().nullable(),
  province: z.string().nullable(),
  upvotes: z.number(),
  downvotes: z.number(),
  is_verified: z.boolean(),
  created_at: z.string(),
  updated_at: z.string(),
})
export type CommunityPriceReport = z.infer<typeof communityPriceReportSchema>
