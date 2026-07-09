import z from 'zod'
import { createFileRoute } from '@tanstack/react-router'
import { Tindahan } from '@/features/tindahan'

const tindahanSearchSchema = z.object({
  page: z.number().optional().catch(1),
  pageSize: z.number().optional().catch(10),
  search: z.string().optional().catch(''),
  is_active: z
    .array(z.enum(['active', 'inactive']))
    .optional()
    .catch([]),
  is_verified: z
    .array(z.enum(['verified', 'unverified']))
    .optional()
    .catch([]),
})

export const Route = createFileRoute('/_authenticated/tindahan/')({
  validateSearch: tindahanSearchSchema,
  component: Tindahan,
})
