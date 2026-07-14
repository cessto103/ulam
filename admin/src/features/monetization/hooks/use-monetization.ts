import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'

export type PlanPrice = {
  id: number
  seller_plan_id: number
  duration: '7d' | '15d' | '1m' | '1y'
  price: string
  is_active: boolean
}

export type SellerPlan = {
  id: number
  slug: string
  name: string
  tagline: string | null
  max_stores: number
  max_items_per_store: number
  sort: number
  is_active: boolean
  prices: PlanPrice[]
}

export type BoostOption = {
  id: number
  target: 'tindahan' | 'recipe'
  duration_days: number
  price: string
  is_active: boolean
}

export type PaymentSettings = {
  payments_enabled: string | null
  gcash_number: string | null
  gcash_account_name: string | null
  payment_instructions: string | null
  payment_support_note: string | null
}

export type PremiumFeature = {
  emoji: string
  title_en: string
  title_tl: string
  desc_en: string
  desc_tl: string
  free: boolean
}

const PLANS_KEY = 'admin-seller-plans'
const SETTINGS_KEY = 'admin-app-settings'
const PREMIUM_FEATURES_KEY = 'admin-premium-features'

export function useSellerPlansQuery() {
  return useQuery({
    queryKey: [PLANS_KEY],
    queryFn: async () => {
      const { data } = await apiClient.get<{
        plans: SellerPlan[]
        boost_options: BoostOption[]
      }>('/admin/seller-plans')
      return data
    },
  })
}

export function useUpdatePlan() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async ({
      id,
      ...body
    }: {
      id: number
      name?: string
      tagline?: string | null
      max_stores?: number
      max_items_per_store?: number
      is_active?: boolean
    }) => {
      const { data } = await apiClient.patch(`/admin/seller-plans/${id}`, body)
      return data
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: [PLANS_KEY] }),
  })
}

export function useUpdatePlanPrices() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async ({
      id,
      prices,
    }: {
      id: number
      prices: { duration: string; price: number; is_active?: boolean }[]
    }) => {
      const { data } = await apiClient.put(`/admin/seller-plans/${id}/prices`, {
        prices,
      })
      return data
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: [PLANS_KEY] }),
  })
}

export function useUpdateBoostOption() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async ({
      id,
      ...body
    }: {
      id: number
      price?: number
      is_active?: boolean
    }) => {
      const { data } = await apiClient.patch(`/admin/boost-options/${id}`, body)
      return data
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: [PLANS_KEY] }),
  })
}

export function usePaymentSettingsQuery() {
  return useQuery({
    queryKey: [SETTINGS_KEY],
    queryFn: async () => {
      const { data } = await apiClient.get<{ settings: PaymentSettings }>(
        '/admin/app-settings'
      )
      return data.settings
    },
  })
}

export function useUpdatePaymentSettings() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (body: {
      payments_enabled?: boolean
      gcash_number?: string | null
      gcash_account_name?: string | null
      payment_instructions?: string | null
      payment_support_note?: string | null
    }) => {
      const { data } = await apiClient.put('/admin/app-settings', body)
      return data
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: [SETTINGS_KEY] }),
  })
}

export function usePremiumFeaturesQuery() {
  return useQuery({
    queryKey: [PREMIUM_FEATURES_KEY],
    queryFn: async () => (await apiClient.get<{ features: PremiumFeature[] }>('/admin/premium-features')).data.features,
  })
}

export function useUpdatePremiumFeatures() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (features: PremiumFeature[]) =>
      (await apiClient.put<{ features: PremiumFeature[] }>('/admin/premium-features', { features })).data,
    onSuccess: () => qc.invalidateQueries({ queryKey: [PREMIUM_FEATURES_KEY] }),
  })
}

export function useResetPremiumFeatures() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async () => apiClient.delete('/admin/premium-features'),
    onSuccess: () => qc.invalidateQueries({ queryKey: [PREMIUM_FEATURES_KEY] }),
  })
}
