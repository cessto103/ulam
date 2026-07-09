import { z } from 'zod'

const commentAuthorSchema = z.object({
  id: z.number(),
  name: z.string(),
  username: z.string(),
  avatar: z.string().nullable(),
})

const commentPostSchema = z.object({
  id: z.number(),
  body: z.string(),
})

const commentSchema = z.object({
  id: z.number(),
  user_id: z.number(),
  user: commentAuthorSchema,
  post_id: z.number(),
  post: commentPostSchema,
  parent_id: z.number().nullable(),
  body: z.string(),
  created_at: z.string(),
  updated_at: z.string(),
})
export type PostComment = z.infer<typeof commentSchema>
