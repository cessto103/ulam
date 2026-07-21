import { useQuery } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'

export type UserSubscription = {
  id: number
  status: string
  current_period_start: string | null
  current_period_end: string | null
  cancel_at_period_end: boolean
  cancelled_at: string | null
  plan: { id: number; name: string; slug: string } | null
  price: { id: number; duration: string; price: string } | null
}

export type UserSellerSubscription = {
  id: number
  plan: string
  duration: string
  amount_paid: string
  status: string
  starts_at: string | null
  expires_at: string | null
  refunded_at: string | null
  tindahan: { id: number; name: string } | null
}

export type UserPayment = {
  id: number
  provider: string
  plan_type: string | null
  amount: number
  currency: string
  status: string
  failure_message: string | null
  paid_at: string | null
  refunded_at: string | null
  created_at: string
}

export type UserRefund = {
  id: number
  payment_id: number
  provider: string
  amount: number
  currency: string
  reason: string | null
  status: string
  processed_at: string | null
  created_at: string
}

export type UserMonetization = {
  premium: {
    plan: 'libre' | 'premium'
    is_premium: boolean
    premium_source: 'paid' | 'trial' | null
    premium_expires_at: string | null
  }
  subscriptions: UserSubscription[]
  seller_subscriptions: UserSellerSubscription[]
  payments: UserPayment[]
  refunds: UserRefund[]
}

export function useUserMonetizationQuery(userId: number | null) {
  return useQuery({
    queryKey: ['admin-user-monetization', userId],
    queryFn: async () => {
      const { data } = await apiClient.get<UserMonetization>(`/admin/users/${userId}/monetization`)
      return data
    },
    enabled: userId != null,
  })
}
