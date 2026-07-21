import z from 'zod'
import { createFileRoute } from '@tanstack/react-router'
import { Invoices } from '@/features/invoices'

const invoicesSearchSchema = z.object({
  page: z.number().optional().catch(1),
  pageSize: z.number().optional().catch(10),
  search: z.string().optional().catch(''),
  status: z.array(z.enum(['draft', 'issued', 'void'])).optional().catch([]),
})

export const Route = createFileRoute('/_authenticated/invoices/')({
  validateSearch: invoicesSearchSchema,
  component: Invoices,
})
