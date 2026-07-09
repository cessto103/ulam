import z from 'zod'
import { createFileRoute } from '@tanstack/react-router'
import { GovernmentPriceReferences } from '@/features/government-price-references'

const governmentPriceReferencesSearchSchema = z.object({
  page: z.number().optional().catch(1),
  pageSize: z.number().optional().catch(10),
  search: z.string().optional().catch(''),
  source: z.array(z.enum(['da_bantay_presyo', 'dti_srp'])).optional().catch([]),
  region: z.array(z.string()).optional().catch([]),
})

export const Route = createFileRoute(
  '/_authenticated/government-price-references/'
)({
  validateSearch: governmentPriceReferencesSearchSchema,
  component: GovernmentPriceReferences,
})
