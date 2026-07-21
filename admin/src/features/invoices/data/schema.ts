import { z } from 'zod'

export const invoiceStatuses = ['draft', 'issued', 'void'] as const

const sponsoredAdRefSchema = z
  .object({
    id: z.number(),
    product_name: z.string(),
    company_name: z.string(),
  })
  .nullable()

const voidedByUserSchema = z
  .object({
    id: z.number(),
    name: z.string(),
  })
  .nullable()

export const invoiceSchema = z.object({
  id: z.number(),
  invoice_number: z.string().nullable(),
  status: z.enum(invoiceStatuses),
  sponsored_ad_id: z.number().nullable(),
  created_by: z.number().nullable(),
  buyer_name: z.string(),
  buyer_contact_name: z.string().nullable(),
  buyer_email: z.string().nullable(),
  buyer_address: z.string().nullable(),
  description: z.string(),
  amount: z.string(),
  vat_status: z.string().nullable(),
  net_amount: z.string().nullable(),
  vat_amount: z.string().nullable(),
  issuer_snapshot: z.record(z.string(), z.unknown()).nullable(),
  pdf_path: z.string().nullable(),
  issued_at: z.string().nullable(),
  voided_at: z.string().nullable(),
  voided_by: z.number().nullable(),
  void_reason: z.string().nullable(),
  notes: z.string().nullable(),
  created_at: z.string(),
  updated_at: z.string(),
  sponsored_ad: sponsoredAdRefSchema.optional(),
  voided_by_user: voidedByUserSchema.optional(),
})
export type Invoice = z.infer<typeof invoiceSchema>
