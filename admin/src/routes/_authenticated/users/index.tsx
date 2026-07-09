import z from 'zod'
import { createFileRoute } from '@tanstack/react-router'
import { Users } from '@/features/users'

const usersSearchSchema = z.object({
  page: z.number().optional().catch(1),
  pageSize: z.number().optional().catch(10),
  search: z.string().optional().catch(''),
  role: z.array(z.enum(['admin', 'user'])).optional().catch([]),
  plan: z.array(z.enum(['premium', 'libre'])).optional().catch([]),
  banned: z.array(z.literal('banned')).optional().catch([]),
})

export const Route = createFileRoute('/_authenticated/users/')({
  validateSearch: usersSearchSchema,
  component: Users,
})
