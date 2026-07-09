import { useQuery } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'
import { type Payment } from '../data/schema'

type PaymentsSearch = {
  page?: number
  pageSize?: number
  search?: string
  plan_type?: string[]
}

type PaginatedResponse<T> = {
  data: T[]
  current_page: number
  last_page: number
  total: number
}

export function usePaymentsQuery(search: PaymentsSearch) {
  return useQuery({
    queryKey: ['admin-payments', search],
    queryFn: async () => {
      const { data } = await apiClient.get<PaginatedResponse<Payment>>(
        '/admin/payments',
        {
          params: {
            page: search.page,
            per_page: search.pageSize,
            search: search.search || undefined,
            plan_type: search.plan_type?.[0],
          },
        }
      )
      return data
    },
    placeholderData: (prev) => prev,
  })
}
