import z from 'zod'
import { createFileRoute } from '@tanstack/react-router'
import { ContentReports } from '@/features/content-reports'

const contentReportsSearchSchema = z.object({
  page: z.number().optional().catch(1),
  pageSize: z.number().optional().catch(10),
  status: z
    .array(z.enum(['pending', 'actioned', 'dismissed']))
    .optional()
    .catch([]),
  content_type: z
    .array(z.enum(['post', 'recipe', 'tindahan']))
    .optional()
    .catch([]),
})

export const Route = createFileRoute('/_authenticated/content-reports/')({
  validateSearch: contentReportsSearchSchema,
  component: ContentReports,
})
