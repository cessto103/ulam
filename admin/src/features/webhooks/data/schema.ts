import { z } from 'zod'

export const webhookStatuses = ['received', 'processed', 'ignored', 'failed'] as const

const webhookEventSchema = z.object({
  id: z.number(),
  provider_event_id: z.string(),
  event_type: z.string(),
  livemode: z.boolean(),
  status: z.enum(webhookStatuses),
  processed_at: z.string().nullable(),
  error: z.string().nullable(),
  created_at: z.string(),
})
export type WebhookEvent = z.infer<typeof webhookEventSchema>
