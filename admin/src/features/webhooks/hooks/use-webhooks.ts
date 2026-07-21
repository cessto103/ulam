import { useQuery } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'
import { type WebhookEvent } from '../data/schema'

type WebhooksSearch = {
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

export function useWebhooksQuery(search: WebhooksSearch) {
  return useQuery({
    queryKey: ['admin-webhooks', search],
    queryFn: async () => {
      const { data } = await apiClient.get<PaginatedResponse<WebhookEvent>>(
        '/admin/billing/webhooks',
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
