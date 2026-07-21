import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'

export type UserSession = {
  id: number
  device_name: string | null
  platform: string | null
  app_version: string | null
  ip_address: string | null
  last_used_at: string | null
  created_at: string
}

export function useUserSessionsQuery(userId: number | null) {
  return useQuery({
    queryKey: ['admin-user-sessions', userId],
    queryFn: async () => {
      const { data } = await apiClient.get<{ sessions: UserSession[] }>(
        `/admin/users/${userId}/sessions`
      )
      return data.sessions
    },
    enabled: userId != null,
  })
}

export function useRevokeUserSession(userId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (tokenId: number) =>
      apiClient.delete(`/admin/users/${userId}/sessions/${tokenId}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-user-sessions', userId] }),
  })
}
