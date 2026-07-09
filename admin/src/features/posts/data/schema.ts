import { z } from 'zod'

const postAuthorSchema = z.object({
  id: z.number(),
  name: z.string(),
  username: z.string(),
  avatar: z.string().nullable(),
})

const postSchema = z.object({
  id: z.number(),
  user_id: z.number(),
  user: postAuthorSchema,
  post_type: z.union([
    z.literal('recipe_share'),
    z.literal('price_tip'),
    z.literal('budget_win'),
    z.literal('general'),
  ]),
  body: z.string(),
  images: z.array(z.string()).nullable(),
  barangay: z.string().nullable(),
  municipality: z.string().nullable(),
  budget_amount: z.number().nullable(),
  serving_size: z.number().nullable(),
  is_sponsored: z.boolean(),
  tindahan_id: z.number().nullable(),
  puso_count: z.number(),
  dislike_count: z.number(),
  comments_count: z.number(),
  recipe_id: z.number().nullable(),
  created_at: z.string(),
  updated_at: z.string(),
})
export type Post = z.infer<typeof postSchema>
