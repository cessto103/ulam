import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'
import { type Market } from '../data/schema'

type MarketsSearch = {
  page?: number
  pageSize?: number
  search?: string
  type?: string[]
  is_active?: string[]
  municipality?: string
}

type PaginatedResponse<T> = {
  data: T[]
  current_page: number
  last_page: number
  total: number
}

export type MarketStall = {
  id: number
  name: string
  type: string
  barangay: string | null
  municipality: string | null
  is_active: boolean
  is_verified: boolean
  average_rating: number
  ratings_count: number
  prices_count: number
}

export type MarketPriceRow = {
  id: number
  item_name: string
  category: string
  price_per_unit: string
  unit: string
  is_available: boolean
  updated_at: string
  tindahan_id: number | null
  tindahan: { id: number; name: string } | null
}

export type MarketDetail = Market & {
  source: string | null
  osm_id: number | null
  user: { id: number; name: string; email: string } | null
  tindahan: MarketStall[]
  prices: MarketPriceRow[]
}

export function useMarketDetailQuery(id: number) {
  return useQuery({
    queryKey: ['admin-market-detail', id],
    queryFn: async () =>
      (await apiClient.get<{ market: MarketDetail }>(`/admin/markets/${id}`)).data.market,
  })
}

export function useMarketsQuery(search: MarketsSearch) {
  return useQuery({
    queryKey: ['admin-markets', search],
    queryFn: async () => {
      const { data } = await apiClient.get<PaginatedResponse<Market>>(
        '/admin/markets',
        {
          params: {
            page: search.page,
            per_page: search.pageSize,
            search: search.search || undefined,
            type: search.type?.[0],
            is_active:
              search.is_active?.[0] === 'active'
                ? true
                : search.is_active?.[0] === 'inactive'
                  ? false
                  : undefined,
            municipality: search.municipality || undefined,
          },
        }
      )
      return data
    },
    placeholderData: (prev) => prev,
  })
}

// Lightweight list used to populate "belongs to market" selects on other
// resources (Tindahan, Market Prices). There is no dedicated list/options
// endpoint, so this just pages through /admin/markets with a high per_page.
export function useMarketOptionsQuery() {
  return useQuery({
    queryKey: ['admin-markets-options'],
    queryFn: async () => {
      const { data } = await apiClient.get<PaginatedResponse<Market>>(
        '/admin/markets',
        { params: { per_page: 200 } }
      )
      return data.data
    },
    staleTime: 5 * 60 * 1000,
  })
}

type MarketInput = {
  name: string
  type: string
  barangay?: string | null
  municipality?: string | null
  province?: string | null
  region?: string | null
  latitude?: number | null
  longitude?: number | null
  is_active?: boolean
}

export function useCreateMarket() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: MarketInput) =>
      apiClient.post('/admin/markets', input),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-markets'] }),
  })
}

type UpdateMarketInput = Partial<MarketInput> & { id: number }

export function useUpdateMarket() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, ...input }: UpdateMarketInput) =>
      apiClient.patch(`/admin/markets/${id}`, input),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-markets'] }),
  })
}

export function useDeleteMarket() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => apiClient.delete(`/admin/markets/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-markets'] }),
  })
}

export function useRefreshMarketAi() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) =>
      apiClient.post<{ message: string }>(`/admin/markets/${id}/refresh-ai`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-markets'] }),
  })
}

type RefreshAllResult = {
  message: string
  total: number
  results: { market: string; count?: number; error?: string }[]
}

export function useRefreshAllMarketsAi() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: () =>
      apiClient.post<RefreshAllResult>('/admin/markets/refresh-ai-all'),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-markets'] }),
  })
}
