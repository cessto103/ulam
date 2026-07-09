import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'
import { type MarketPrice } from '../data/schema'

type MarketPricesSearch = {
  page?: number
  pageSize?: number
  search?: string
  market_id?: number
  tindahan_id?: number
  category?: string[]
  is_available?: string[]
}

type PaginatedResponse<T> = {
  data: T[]
  current_page: number
  last_page: number
  total: number
}

export function useMarketPricesQuery(search: MarketPricesSearch) {
  return useQuery({
    queryKey: ['admin-market-prices', search],
    queryFn: async () => {
      const { data } = await apiClient.get<PaginatedResponse<MarketPrice>>(
        '/admin/market-prices',
        {
          params: {
            page: search.page,
            per_page: search.pageSize,
            search: search.search || undefined,
            market_id: search.market_id,
            tindahan_id: search.tindahan_id,
            category: search.category?.[0],
            is_available:
              search.is_available?.[0] === 'available'
                ? true
                : search.is_available?.[0] === 'unavailable'
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

type MarketPriceInput = {
  market_id?: number | null
  tindahan_id?: number | null
  item_name: string
  category?: string | null
  price_per_unit: number
  unit: string
  is_available?: boolean
}

export function useCreateMarketPrice() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: MarketPriceInput) =>
      apiClient.post('/admin/market-prices', input),
    onSuccess: () =>
      qc.invalidateQueries({ queryKey: ['admin-market-prices'] }),
  })
}

type UpdateMarketPriceInput = Partial<MarketPriceInput> & { id: number }

export function useUpdateMarketPrice() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, ...input }: UpdateMarketPriceInput) =>
      apiClient.patch(`/admin/market-prices/${id}`, input),
    onSuccess: () =>
      qc.invalidateQueries({ queryKey: ['admin-market-prices'] }),
  })
}

export function useDeleteMarketPrice() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => apiClient.delete(`/admin/market-prices/${id}`),
    onSuccess: () =>
      qc.invalidateQueries({ queryKey: ['admin-market-prices'] }),
  })
}
