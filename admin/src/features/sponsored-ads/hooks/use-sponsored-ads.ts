import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'
import { type SponsoredAd } from '../data/schema'

type SponsoredAdsSearch = {
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

const QUERY_KEY = 'admin-sponsored-ads'

export function useSponsoredAdsQuery(search: SponsoredAdsSearch) {
  return useQuery({
    queryKey: [QUERY_KEY, search],
    queryFn: async () => {
      const { data } = await apiClient.get<PaginatedResponse<SponsoredAd>>(
        '/admin/sponsored-ads',
        {
          params: {
            page: search.page,
            per_page: search.pageSize,
            search: search.search || undefined,
            status: search.status?.[0],
          },
        }
      )
      return data
    },
    placeholderData: (prev) => prev,
  })
}

export type SponsoredAdInput = {
  product_name?: string
  company_name?: string
  tagline?: string | null
  description?: string | null
  image_url?: string | null
  link_url?: string | null
  cta_label?: string | null
  amount_paid?: number
  payment_received_at?: string | null
  start_date?: string
  end_date?: string
  is_enabled?: boolean
  show_to_free?: boolean
  show_to_premium?: boolean
  show_in_recipe_feed?: boolean
  show_in_community_feed?: boolean
  contact_name?: string | null
  contact_email?: string | null
  notes?: string | null
}

export function useCreateSponsoredAd() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: SponsoredAdInput) =>
      apiClient.post('/admin/sponsored-ads', input),
    onSuccess: () => qc.invalidateQueries({ queryKey: [QUERY_KEY] }),
  })
}

type UpdateSponsoredAdInput = SponsoredAdInput & { id: number }

export function useUpdateSponsoredAd() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, ...input }: UpdateSponsoredAdInput) =>
      apiClient.patch(`/admin/sponsored-ads/${id}`, input),
    onSuccess: () => qc.invalidateQueries({ queryKey: [QUERY_KEY] }),
  })
}

export function useDeleteSponsoredAd() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => apiClient.delete(`/admin/sponsored-ads/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: [QUERY_KEY] }),
  })
}

export function useToggleEnabledSponsoredAd() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, is_enabled }: { id: number; is_enabled: boolean }) =>
      apiClient.patch(`/admin/sponsored-ads/${id}`, { is_enabled }),
    onSuccess: () => qc.invalidateQueries({ queryKey: [QUERY_KEY] }),
  })
}

export function useUploadSponsoredAdImage() {
  return useMutation({
    mutationFn: async (file: File) => {
      const form = new FormData()
      form.append('image', file)
      const { data } = await apiClient.post<{ url: string }>(
        '/admin/sponsored-ads/upload-image',
        form,
        { headers: { 'Content-Type': 'multipart/form-data' } }
      )
      return data.url
    },
  })
}
