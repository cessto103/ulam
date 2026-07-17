import z from 'zod'
import { createFileRoute } from '@tanstack/react-router'
import { Recipes } from '@/features/recipes'

const recipesSearchSchema = z.object({
  page: z.number().optional().catch(1),
  pageSize: z.number().optional().catch(10),
  search: z.string().optional().catch(''),
  source: z
    .array(z.enum(['ai_generated', 'community', 'admin', 'official']))
    .optional()
    .catch([]),
  budget_tag: z
    .array(
      z.enum([
        'budget_100',
        'budget_200',
        'budget_400',
        'budget_600',
        'budget_800',
        'budget_1000',
        'budget_1000plus',
      ])
    )
    .optional()
    .catch([]),
  is_published: z
    .array(z.enum(['published', 'unpublished']))
    .optional()
    .catch([]),
})

export const Route = createFileRoute('/_authenticated/recipes/')({
  validateSearch: recipesSearchSchema,
  component: Recipes,
})
