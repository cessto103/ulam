import { z } from 'zod'

const relatedUserSchema = z.object({
  id: z.number(),
  name: z.string(),
  username: z.string().nullable().optional(),
})

const strikeSchema = z.object({
  level: z.number(),
  level_label: z.string(),
  reason: z.string(),
  issued_by: z.string().nullable(),
  created_at: z.string(),
  expires_at: z.string().nullable(),
})

// Present on show() (single-report fetch), used by the Warn/Restrict/Ban
// dialogs for context. Not returned by index() -- keeping it off the list
// payload avoids an N+1 strike-history lookup per row on every page load.
const strikeSummarySchema = z.object({
  active_count: z.number(),
  recent: z.array(strikeSchema),
})

const contentReportSchema = z.object({
  id: z.number(),
  user_id: z.number(),
  reporter: relatedUserSchema.nullable(),
  reported_user_id: z.number().nullable(),
  // Eloquent snake_cases a relation's array/JSON key by default, so the
  // reportedUser() relation serializes as `reported_user` -- not camelCase.
  // (resolvedBy() would snake_case to `resolved_by` too, colliding with the
  // real resolved_by FK column, so it's deliberately not eager-loaded here.)
  reported_user: relatedUserSchema.nullable(),
  content_type: z.union([z.literal('post'), z.literal('recipe'), z.literal('tindahan')]),
  content_id: z.number(),
  content_preview: z.string().nullable(),
  content_exists: z.boolean(),
  reason: z.string(),
  details: z.string().nullable(),
  status: z.union([z.literal('pending'), z.literal('actioned'), z.literal('dismissed')]),
  // Eager-loaded on index()/show(), so this is always the resolvedBy()
  // relation object (or null) here, never the raw resolved_by FK integer.
  resolved_by: z.object({ id: z.number(), name: z.string() }).nullable(),
  resolved_at: z.string().nullable(),
  created_at: z.string(),
  updated_at: z.string(),
  reported_user_strikes: strikeSummarySchema.optional(),
})

export type ContentReport = z.infer<typeof contentReportSchema>
export type StrikeSummary = z.infer<typeof strikeSummarySchema>
