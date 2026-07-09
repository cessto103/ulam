import { z } from 'zod'

const governmentPriceReferenceSchema = z.object({
  id: z.number(),
  source: z.union([z.literal('da_bantay_presyo'), z.literal('dti_srp')]),
  item_name: z.string(),
  category: z.string().nullable(),
  price_min: z.string(),
  price_max: z.string(),
  unit: z.string(),
  region: z.string().nullable(),
  bulletin_date: z.string().nullable(),
  source_note: z.string().nullable(),
  created_at: z.string(),
  updated_at: z.string(),
})
export type GovernmentPriceReference = z.infer<
  typeof governmentPriceReferenceSchema
>
