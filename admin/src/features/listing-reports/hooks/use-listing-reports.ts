import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'
import { type ListingReport } from '../data/schema'

type ListingReportsSearch = {
  page?: number
  pageSize?: number
  status?: string[]
  reportable_type?: string[]
}

type PaginatedResponse<T> = {
  data: T[]
  current_page: number
  last_page: number
  total: number
}

const QUERY_KEY = 'admin-listing-reports'

export function useListingReportsQuery(search: ListingReportsSearch) {
  return useQuery({
    queryKey: [QUERY_KEY, search],
    queryFn: async () => {
      const { data } = await apiClient.get<PaginatedResponse<ListingReport>>(
        '/admin/listing-reports',
        {
          params: {
            page: search.page,
            per_page: search.pageSize,
            status: search.status?.[0],
            reportable_type: search.reportable_type?.[0],
          },
        }
      )
      return data
    },
    placeholderData: (prev) => prev,
  })
}

export function useDeleteListingReport() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => apiClient.delete(`/admin/listing-reports/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: [QUERY_KEY] }),
  })
}

export function useBanListingOwner() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) =>
      apiClient.post<{ report: ListingReport; message?: string }>(
        `/admin/listing-reports/${id}/ban-owner`
      ),
    onSuccess: () => qc.invalidateQueries({ queryKey: [QUERY_KEY] }),
  })
}

export function useDeactivateListing() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) =>
      apiClient.post<{ report: ListingReport }>(
        `/admin/listing-reports/${id}/deactivate-listing`
      ),
    onSuccess: () => qc.invalidateQueries({ queryKey: [QUERY_KEY] }),
  })
}

export function useDismissListingReport() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) =>
      apiClient.post<{ report: ListingReport }>(
        `/admin/listing-reports/${id}/dismiss`
      ),
    onSuccess: () => qc.invalidateQueries({ queryKey: [QUERY_KEY] }),
  })
}
