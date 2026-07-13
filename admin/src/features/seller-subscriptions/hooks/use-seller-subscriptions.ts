import { useQuery } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'

export type BillingSubscription = {
  id: number
  status: string
  provider: string
  current_period_start: string | null
  current_period_end: string | null
  grace_ends_at: string | null
  cancel_at_period_end: boolean
  created_at: string
  user: { id: number; name: string; username: string; email: string } | null
  plan: { id: number; slug: string; name: string }
  price: { id: number; duration: string; price: string } | null
}

type Page<T> = { data: T[]; current_page: number; last_page: number; total: number }

export type BillingSummary = {
  active_subscribers: number
  monthly_revenue: number
  annual_revenue: number
  failed_payments: number
  churn_rate: number
  expiring_soon: number
  webhook_failures: number
}

export type WebhookRow = {
  id: number
  provider_event_id: string
  event_type: string
  livemode: boolean
  status: string
  processed_at: string | null
  error: string | null
  created_at: string
}

export function useBillingSummary() {
  return useQuery({
    queryKey: ['admin-billing-summary'],
    queryFn: async () => (await apiClient.get<BillingSummary>('/admin/billing/summary')).data,
  })
}

export function useBillingSubscriptions(params: { page: number; status?: string }) {
  return useQuery({
    queryKey: ['admin-billing-subscriptions', params],
    queryFn: async () => (await apiClient.get<Page<BillingSubscription>>('/admin/billing/subscriptions', { params })).data,
    placeholderData: (previous) => previous,
  })
}

export function useBillingWebhooks(params: { page: number; status?: string }) {
  return useQuery({
    queryKey: ['admin-billing-webhooks', params],
    queryFn: async () => (await apiClient.get<Page<WebhookRow>>('/admin/billing/webhooks', { params })).data,
    placeholderData: (previous) => previous,
  })
}
