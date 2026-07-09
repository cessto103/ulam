import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'
import { type Tindahan } from '../data/schema'

type TindahanSearch = {
  page?: number
  pageSize?: number
  search?: string
  is_active?: string[]
  is_verified?: string[]
}

type PaginatedResponse<T> = {
  data: T[]
  current_page: number
  last_page: number
  total: number
}

export function useTindahanQuery(search: TindahanSearch) {
  return useQuery({
    queryKey: ['admin-tindahan', search],
    queryFn: async () => {
      const { data } = await apiClient.get<PaginatedResponse<Tindahan>>(
        '/admin/tindahan',
        {
          params: {
            page: search.page,
            per_page: search.pageSize,
            search: search.search || undefined,
            is_active:
              search.is_active?.[0] === 'active'
                ? true
                : search.is_active?.[0] === 'inactive'
                  ? false
                  : undefined,
            is_verified:
              search.is_verified?.[0] === 'verified'
                ? true
                : search.is_verified?.[0] === 'unverified'
                  ? false
                  : undefined,
          },
        }
      )
      return data
    },
    placeholderData: (prev) => prev,
  })
}

type TindahanInput = {
  name: string
  market_id?: number | null
  type?: string | null
  description?: string | null
  barangay?: string | null
  municipality?: string | null
  province?: string | null
  region?: string | null
  contact_number?: string | null
  gcash_number?: string | null
  is_active?: boolean
  is_verified?: boolean
}

export function useCreateTindahan() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: TindahanInput) =>
      apiClient.post('/admin/tindahan', input),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-tindahan'] }),
  })
}

type UpdateTindahanInput = Partial<TindahanInput> & { id: number }

export function useUpdateTindahan() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, ...input }: UpdateTindahanInput) =>
      apiClient.patch(`/admin/tindahan/${id}`, input),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-tindahan'] }),
  })
}

export function useDeleteTindahan() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => apiClient.delete(`/admin/tindahan/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-tindahan'] }),
  })
}

// Lightweight list used to populate the "parent market" select on the
// tindahan form. There is no dedicated list/options endpoint, so this just
// pages through /admin/tindahan with a high per_page.
export function useTindahanOptionsQuery() {
  return useQuery({
    queryKey: ['admin-tindahan-options'],
    queryFn: async () => {
      const { data } = await apiClient.get<PaginatedResponse<Tindahan>>(
        '/admin/tindahan',
        { params: { per_page: 200 } }
      )
      return data.data
    },
    staleTime: 5 * 60 * 1000,
  })
}
