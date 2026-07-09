import z from 'zod'
import { createFileRoute } from '@tanstack/react-router'
import { ListingReports } from '@/features/listing-reports'

const listingReportsSearchSchema = z.object({
  page: z.number().optional().catch(1),
  pageSize: z.number().optional().catch(10),
  status: z
    .array(z.enum(['pending', 'actioned', 'dismissed']))
    .optional()
    .catch([]),
  reportable_type: z.array(z.enum(['market', 'tindahan'])).optional().catch([]),
})

export const Route = createFileRoute('/_authenticated/listing-reports/')({
  validateSearch: listingReportsSearchSchema,
  component: ListingReports,
})
