import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'
import { type Invoice } from '../data/schema'

type InvoicesSearch = {
  page?: number
  pageSize?: number
  search?: string
  status?: string[]
}

type PaginatedResponse<T> = {
  data: T[]
  current_page: number
  last_page: number
  total: number
}

const QUERY_KEY = 'admin-invoices'

export function useInvoicesQuery(search: InvoicesSearch) {
  return useQuery({
    queryKey: [QUERY_KEY, search],
    queryFn: async () => {
      const { data } = await apiClient.get<PaginatedResponse<Invoice>>('/admin/invoices', {
        params: {
          page: search.page,
          per_page: search.pageSize,
          search: search.search || undefined,
          status: search.status?.[0],
        },
      })
      return data
    },
    placeholderData: (prev) => prev,
  })
}

export function useInvoiceQuery(id: number | null) {
  return useQuery({
    queryKey: [QUERY_KEY, 'detail', id],
    queryFn: async () => (await apiClient.get<{ invoice: Invoice }>(`/admin/invoices/${id}`)).data.invoice,
    enabled: id !== null,
  })
}

function invalidateAll(qc: ReturnType<typeof useQueryClient>) {
  qc.invalidateQueries({ queryKey: [QUERY_KEY] })
}

export type InvoiceInput = {
  sponsored_ad_id?: number | null
  buyer_name: string
  buyer_contact_name?: string | null
  buyer_email?: string | null
  buyer_address?: string | null
  description: string
  amount: number
  notes?: string | null
}

export function useCreateInvoice() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: InvoiceInput) => apiClient.post('/admin/invoices', input),
    onSuccess: () => invalidateAll(qc),
  })
}

type UpdateInvoiceInput = Partial<InvoiceInput> & { id: number }

export function useUpdateInvoice() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, ...input }: UpdateInvoiceInput) => apiClient.patch(`/admin/invoices/${id}`, input),
    onSuccess: () => invalidateAll(qc),
  })
}

export function useDeleteInvoice() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => apiClient.delete(`/admin/invoices/${id}`),
    onSuccess: () => invalidateAll(qc),
  })
}

export function useMarkInvoiceAsPaid() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => apiClient.post(`/admin/invoices/${id}/mark-paid`),
    onSuccess: () => invalidateAll(qc),
  })
}

export function useVoidInvoice() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, reason }: { id: number; reason: string }) =>
      apiClient.post(`/admin/invoices/${id}/void`, { reason }),
    onSuccess: () => invalidateAll(qc),
  })
}

export function useEmailInvoice() {
  return useMutation({
    mutationFn: ({ id, to }: { id: number; to?: string }) =>
      apiClient.post<{ message: string }>(`/admin/invoices/${id}/email`, { to }),
  })
}

/** Lightweight list for the "Link to Sponsored Ad" picker -- a separate,
 * feature-local type rather than importing the sponsored-ads feature's own
 * schema, matching this codebase's convention of each feature owning its own
 * API-shape types even where they overlap. */
export type SponsoredAdOption = {
  id: number
  product_name: string
  company_name: string
  amount_paid: string
  contact_name: string | null
  contact_email: string | null
}

export function useSponsoredAdOptions() {
  return useQuery({
    queryKey: ['admin-sponsored-ads-for-invoice-picker'],
    queryFn: async () => {
      const { data } = await apiClient.get<PaginatedResponse<SponsoredAdOption>>('/admin/sponsored-ads', {
        params: { per_page: 100 },
      })
      return data.data
    },
    staleTime: 60_000,
  })
}

export async function downloadInvoicePdf(id: number, filename: string) {
  const res = await apiClient.get(`/admin/invoices/${id}/pdf`, { responseType: 'blob' })
  const url = window.URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' }))
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  document.body.appendChild(a)
  a.click()
  a.remove()
  window.URL.revokeObjectURL(url)
}

export function useDownloadInvoicePdf() {
  return useMutation({
    mutationFn: ({ id, filename }: { id: number; filename: string }) => downloadInvoicePdf(id, filename),
  })
}
