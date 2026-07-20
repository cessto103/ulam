import z from 'zod'
import { createFileRoute } from '@tanstack/react-router'
import { SponsoredAds } from '@/features/sponsored-ads'

const sponsoredAdsSearchSchema = z.object({
  page: z.number().optional().catch(1),
  pageSize: z.number().optional().catch(10),
  search: z.string().optional().catch(''),
  status: z
    .array(z.enum(['running', 'scheduled', 'ended', 'disabled']))
    .optional()
    .catch([]),
})

export const Route = createFileRoute('/_authenticated/sponsored-ads/')({
  validateSearch: sponsoredAdsSearchSchema,
  component: SponsoredAds,
})
