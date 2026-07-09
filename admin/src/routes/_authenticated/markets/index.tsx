import z from 'zod'
import { createFileRoute } from '@tanstack/react-router'
import { Markets } from '@/features/markets'

const marketsSearchSchema = z.object({
  page: z.number().optional().catch(1),
  pageSize: z.number().optional().catch(10),
  search: z.string().optional().catch(''),
  type: z
    .array(
      z.enum(['wet_market', 'palengke', 'supermarket', 'grocery', 'tindahan'])
    )
    .optional()
    .catch([]),
  is_active: z
    .array(z.enum(['active', 'inactive']))
    .optional()
    .catch([]),
  municipality: z.string().optional().catch(''),
})

export const Route = createFileRoute('/_authenticated/markets/')({
  validateSearch: marketsSearchSchema,
  component: Markets,
})
