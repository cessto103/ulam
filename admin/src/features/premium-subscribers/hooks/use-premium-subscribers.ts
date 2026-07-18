import { useQuery } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'

export type PremiumSubscriber = {
  id: number
  name: string
  username: string
  email: string
  premium_source: 'paid' | 'trial' | null
  premium_expires_at: string | null
  created_at: string
}

type Page<T> = { data: T[]; current_page: number; last_page: number; total: number }

export type PremiumSummary = {
  total: number
  paid: number
  trial: number
  expiring_soon: number
}

export function usePremiumSummary() {
  return useQuery({
    queryKey: ['admin-premium-summary'],
    queryFn: async () =>
      (await apiClient.get<PremiumSummary>('/admin/premium-subscribers/summary')).data,
  })
}

export function usePremiumSubscribers(params: {
  page: number
  source?: string
  expiring_soon?: boolean
}) {
  return useQuery({
    queryKey: ['admin-premium-subscribers', params],
    queryFn: async () =>
      (
        await apiClient.get<Page<PremiumSubscriber>>('/admin/premium-subscribers', {
          params: {
            page: params.page,
            source: params.source,
            expiring_soon: params.expiring_soon ? 1 : undefined,
          },
        })
      ).data,
    placeholderData: (previous) => previous,
  })
}
