import { z } from 'zod'

const tindahanMarketSchema = z.object({
  id: z.number(),
  name: z.string(),
})

const tindahanSchema = z.object({
  id: z.number(),
  name: z.string(),
  market_id: z.number().nullable(),
  market: tindahanMarketSchema.nullable().optional(),
  type: z.string().nullable(),
  description: z.string().nullable(),
  barangay: z.string().nullable(),
  municipality: z.string().nullable(),
  province: z.string().nullable(),
  region: z.string().nullable(),
  contact_number: z.string().nullable(),
  gcash_number: z.string().nullable(),
  is_active: z.boolean(),
  is_verified: z.boolean(),
  created_at: z.string(),
  updated_at: z.string(),
})
export type Tindahan = z.infer<typeof tindahanSchema>
