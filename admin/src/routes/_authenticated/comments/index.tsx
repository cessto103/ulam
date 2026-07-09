import z from 'zod'
import { createFileRoute } from '@tanstack/react-router'
import { Comments } from '@/features/comments'

const commentsSearchSchema = z.object({
  page: z.number().optional().catch(1),
  pageSize: z.number().optional().catch(10),
  search: z.string().optional().catch(''),
  post_id: z.number().optional().catch(undefined),
  is_reply: z
    .array(z.enum(['reply', 'top_level']))
    .optional()
    .catch([]),
})

export const Route = createFileRoute('/_authenticated/comments/')({
  validateSearch: commentsSearchSchema,
  component: Comments,
})
