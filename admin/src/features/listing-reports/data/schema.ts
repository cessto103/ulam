import { z } from 'zod'

const relatedNameSchema = z.object({
  id: z.number(),
  name: z.string(),
})

// The polymorphic `reportable` relation (Market or Tindahan) — loosely typed since
// its shape varies by underlying model and it can be null if the listing was deleted.
const reportableSchema = z
  .object({
    id: z.number(),
    name: z.string(),
    is_active: z.boolean().optional(),
  })
  .loose()

const listingReportSchema = z.object({
  id: z.number(),
  reporter_id: z.number(),
  reporter: relatedNameSchema.nullable(),
  reportable_type: z.string(),
  reportable_id: z.number(),
  reportable: reportableSchema.nullable(),
  reason: z.string(),
  status: z.union([
    z.literal('pending'),
    z.literal('actioned'),
    z.literal('dismissed'),
  ]),
  resolved_by: z.number().nullable(),
  resolvedBy: relatedNameSchema.nullable(),
  resolved_at: z.string().nullable(),
  created_at: z.string(),
  updated_at: z.string(),
})
export type ListingReport = z.infer<typeof listingReportSchema>
