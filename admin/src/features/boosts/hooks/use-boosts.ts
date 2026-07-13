import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'

export type Boost = {
  id: number
  user_id: number
  target: 'recipe' | 'tindahan'
  target_name: string | null
  duration_days: number | null
  amount_paid: string
  payment_method: string
  payment_reference: string | null
  status: 'pending' | 'active' | 'expired' | 'rejected'
  rejected_reason: string | null
  starts_at: string | null
  expires_at: string | null
  created_at: string
  user: { id: number; name: string; username: string; email: string } | null
}

type Page<T> = { data: T[]; current_page: number; last_page: number; total: number }
type Counts = { pending: number; active: number }

const QUERY_KEY = 'admin-boosts'

export function useBoosts(params: { page: number; status?: string; search?: string }) {
  return useQuery({
    queryKey: [QUERY_KEY, params],
    queryFn: async () =>
      (await apiClient.get<Page<Boost> & { counts: Counts }>('/admin/boosts', { params })).data,
    placeholderData: (previous) => previous,
  })
}

export function useApproveBoost() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => apiClient.post(`/admin/boosts/${id}/approve`),
    onSuccess: () => qc.invalidateQueries({ queryKey: [QUERY_KEY] }),
  })
}

export function useRejectBoost() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, reason }: { id: number; reason: string }) =>
      apiClient.post(`/admin/boosts/${id}/reject`, { reason }),
    onSuccess: () => qc.invalidateQueries({ queryKey: [QUERY_KEY] }),
  })
}
