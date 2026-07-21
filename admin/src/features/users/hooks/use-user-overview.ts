import { useQuery } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'

export type UserOverview = {
  stats: {
    xp: number
    level: number
    streak_days: number
    joined_at: string
    email_verified_at: string | null
    last_active_date: string | null
    household_size: number | null
    gender: string | null
    location: string
    bio: string | null
  }
  counts: {
    posts: number
    recipes: number
    stores: number
    followers: number
    following: number
  }
  last_session: {
    device_name: string | null
    platform: string | null
    ip_address: string | null
    last_used_at: string | null
  } | null
  xp_history: { date: string; xp: number }[]
}

export function useUserOverviewQuery(userId: number | null) {
  return useQuery({
    queryKey: ['admin-user-overview', userId],
    queryFn: async () => {
      const { data } = await apiClient.get<UserOverview>(`/admin/users/${userId}/overview`)
      return data
    },
    enabled: userId != null,
  })
}
