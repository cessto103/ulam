import { z } from 'zod'

export const marketTypes = [
  'wet_market',
  'palengke',
  'supermarket',
  'grocery',
  'tindahan',
] as const

const marketSchema = z.object({
  id: z.number(),
  name: z.string(),
  type: z.enum(marketTypes),
  barangay: z.string().nullable(),
  municipality: z.string().nullable(),
  province: z.string().nullable(),
  region: z.string().nullable(),
  latitude: z.number().nullable(),
  longitude: z.number().nullable(),
  is_active: z.boolean(),
  tindahan_count: z.number().nullish(),
  prices_count: z.number().nullish(),
  created_at: z.string(),
  updated_at: z.string(),
})
export type Market = z.infer<typeof marketSchema>
