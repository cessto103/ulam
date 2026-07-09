import { z } from 'zod'

const userSchema = z.object({
  id: z.number(),
  name: z.string(),
  username: z.string(),
  email: z.string(),
  avatar: z.string().nullable(),
  bio: z.string().nullable(),
  plan: z.union([z.literal('libre'), z.literal('premium')]),
  role: z.union([z.literal('user'), z.literal('admin')]),
  banned_at: z.string().nullable(),
  ban_reason: z.string().nullable(),
  municipality: z.string().nullable(),
  xp: z.number(),
  level: z.number(),
  streak_days: z.number(),
  created_at: z.string(),
})
export type User = z.infer<typeof userSchema>
