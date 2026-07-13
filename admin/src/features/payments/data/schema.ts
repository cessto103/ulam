import { z } from 'zod'

const paymentSchema = z.object({
  id: z.number(),
  user_id: z.number().nullable(),
  user: z
    .object({
      id: z.number(),
      name: z.string(),
      username: z.string(),
      email: z.string(),
    })
    .nullable(),
  provider: z.string(),
  provider_payment_id: z.string().nullable(),
  plan_type: z.string(),
  amount: z.number(), // centavos
  currency: z.string(),
  status: z.string(),
  failure_code: z.string().nullable().optional(),
  refunded_at: z.string().nullable().optional(),
  paid_at: z.string(),
  created_at: z.string(),
})
export type Payment = z.infer<typeof paymentSchema>
