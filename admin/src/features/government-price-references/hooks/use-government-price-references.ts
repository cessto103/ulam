import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'
import { type GovernmentPriceReference } from '../data/schema'

type GovernmentPriceReferencesSearch = {
  page?: number
  pageSize?: number
  search?: string
  source?: string[]
  region?: string[]
}

type PaginatedResponse<T> = {
  data: T[]
  current_page: number
  last_page: number
  total: number
}

const QUERY_KEY = 'admin-government-price-references'

export function useGovernmentPriceReferencesQuery(
  search: GovernmentPriceReferencesSearch
) {
  return useQuery({
    queryKey: [QUERY_KEY, search],
    queryFn: async () => {
      const { data } = await apiClient.get<
        PaginatedResponse<GovernmentPriceReference>
      >('/admin/government-price-references', {
        params: {
          page: search.page,
          per_page: search.pageSize,
          search: search.search || undefined,
          source: search.source?.[0],
          region: search.region?.[0],
        },
      })
      return data
    },
    placeholderData: (prev) => prev,
  })
}

type GovernmentPriceReferenceInput = {
  source: string
  item_name: string
  category?: string | null
  price_min: number
  price_max: number
  unit: string
  region?: string | null
  bulletin_date?: string | null
  source_note?: string | null
}

export function useCreateGovernmentPriceReference() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: GovernmentPriceReferenceInput) =>
      apiClient.post('/admin/government-price-references', input),
    onSuccess: () => qc.invalidateQueries({ queryKey: [QUERY_KEY] }),
  })
}

type UpdateGovernmentPriceReferenceInput =
  Partial<GovernmentPriceReferenceInput> & { id: number }

export function useUpdateGovernmentPriceReference() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, ...input }: UpdateGovernmentPriceReferenceInput) =>
      apiClient.patch(`/admin/government-price-references/${id}`, input),
    onSuccess: () => qc.invalidateQueries({ queryKey: [QUERY_KEY] }),
  })
}

export function useDeleteGovernmentPriceReference() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) =>
      apiClient.delete(`/admin/government-price-references/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: [QUERY_KEY] }),
  })
}
