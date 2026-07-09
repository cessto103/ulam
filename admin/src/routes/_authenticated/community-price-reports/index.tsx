import z from 'zod'
import { createFileRoute } from '@tanstack/react-router'
import { CommunityPriceReports } from '@/features/community-price-reports'

const communityPriceReportsSearchSchema = z.object({
  page: z.number().optional().catch(1),
  pageSize: z.number().optional().catch(10),
  search: z.string().optional().catch(''),
  is_verified: z.array(z.enum(['true', 'false'])).optional().catch([]),
  category: z.array(z.string()).optional().catch([]),
})

export const Route = createFileRoute('/_authenticated/community-price-reports/')({
  validateSearch: communityPriceReportsSearchSchema,
  component: CommunityPriceReports,
})
