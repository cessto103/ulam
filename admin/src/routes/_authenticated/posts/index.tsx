import z from 'zod'
import { createFileRoute } from '@tanstack/react-router'
import { Posts } from '@/features/posts'

const postsSearchSchema = z.object({
  page: z.number().optional().catch(1),
  pageSize: z.number().optional().catch(10),
  search: z.string().optional().catch(''),
  post_type: z
    .array(
      z.enum(['recipe_share', 'price_tip', 'budget_win', 'general'])
    )
    .optional()
    .catch([]),
  is_sponsored: z.array(z.literal('sponsored')).optional().catch([]),
  municipality: z.string().optional().catch(''),
})

export const Route = createFileRoute('/_authenticated/posts/')({
  validateSearch: postsSearchSchema,
  component: Posts,
})
