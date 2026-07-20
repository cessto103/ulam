import { z } from 'zod'

export const sponsoredAdStatuses = [
  'running',
  'scheduled',
  'ended',
  'disabled',
] as const

export const sponsoredAdSchema = z.object({
  id: z.number(),
  product_name: z.string(),
  company_name: z.string(),
  tagline: z.string().nullable(),
  description: z.string().nullable(),
  image_url: z.string().nullable(),
  link_url: z.string().nullable(),
  cta_label: z.string().nullable(),
  amount_paid: z.string(),
  payment_received_at: z.string().nullable(),
  start_date: z.string(),
  end_date: z.string(),
  is_enabled: z.boolean(),
  show_to_free: z.boolean(),
  show_to_premium: z.boolean(),
  show_in_recipe_feed: z.boolean(),
  show_in_community_feed: z.boolean(),
  contact_name: z.string().nullable(),
  contact_email: z.string().nullable(),
  notes: z.string().nullable(),
  impressions_count: z.number(),
  clicks_count: z.number(),
  display_status: z.enum(sponsoredAdStatuses),
  is_currently_running: z.boolean(),
  created_at: z.string(),
  updated_at: z.string(),
})
export type SponsoredAd = z.infer<typeof sponsoredAdSchema>
