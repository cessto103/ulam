import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'
import { type CommunityPriceReport } from '../data/schema'

type CommunityPriceReportsSearch = {
  page?: number
  pageSize?: number
  search?: string
  is_verified?: string[]
  category?: string[]
}

type PaginatedResponse<T> = {
  data: T[]
  current_page: number
  last_page: number
  total: number
}

const QUERY_KEY = 'admin-community-price-reports'

export function useCommunityPriceReportsQuery(
  search: CommunityPriceReportsSearch
) {
  return useQuery({
    queryKey: [QUERY_KEY, search],
    queryFn: async () => {
      const isVerified = search.is_verified?.[0]
      const { data } = await apiClient.get<
        PaginatedResponse<CommunityPriceReport>
      >('/admin/community-price-reports', {
        params: {
          page: search.page,
          per_page: search.pageSize,
          search: search.search || undefined,
          is_verified:
            isVerified === 'true' ? true : isVerified === 'false' ? false : undefined,
          category: search.category?.[0],
        },
      })
      return data
    },
    placeholderData: (prev) => prev,
  })
}

type CommunityPriceReportInput = {
  tindahan_id?: number | null
  market_id?: number | null
  item_name: string
  category?: string | null
  reported_price: number
  unit: string
  barangay?: string | null
  municipality?: string | null
  province?: string | null
  is_verified?: boolean
}

export function useCreateCommunityPriceReport() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: CommunityPriceReportInput) =>
      apiClient.post('/admin/community-price-reports', input),
    onSuccess: () => qc.invalidateQueries({ queryKey: [QUERY_KEY] }),
  })
}

type UpdateCommunityPriceReportInput = Partial<CommunityPriceReportInput> & {
  id: number
}

export function useUpdateCommunityPriceReport() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, ...input }: UpdateCommunityPriceReportInput) =>
      apiClient.patch(`/admin/community-price-reports/${id}`, input),
    onSuccess: () => qc.invalidateQueries({ queryKey: [QUERY_KEY] }),
  })
}

export function useDeleteCommunityPriceReport() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) =>
      apiClient.delete(`/admin/community-price-reports/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: [QUERY_KEY] }),
  })
}

export function useVerifyCommunityPriceReport() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) =>
      apiClient.post(`/admin/community-price-reports/${id}/verify`),
    onSuccess: () => qc.invalidateQueries({ queryKey: [QUERY_KEY] }),
  })
}
