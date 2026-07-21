import z from 'zod'
import { createFileRoute } from '@tanstack/react-router'
import { Webhooks } from '@/features/webhooks'
import { webhookStatuses } from '@/features/webhooks/data/schema'

const webhooksSearchSchema = z.object({
  page: z.number().optional().catch(1),
  pageSize: z.number().optional().catch(10),
  search: z.string().optional().catch(''),
  status: z.array(z.enum(webhookStatuses)).optional().catch([]),
})

export const Route = createFileRoute('/_authenticated/webhooks/')({
  validateSearch: webhooksSearchSchema,
  component: Webhooks,
})
