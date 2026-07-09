import { useQuery } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'

type DashboardStats = {
  users: {
    total: number
    active_today: number
    premium: number
    estimated_mrr: number
    banned: number
  }
  content: {
    total_posts: number
    total_recipes: number
  }
  ai_usage: {
    meal_plans_this_month: number
    prompt_tokens: number
    completion_tokens: number
    estimated_cost: number
    note: string
  }
}

export function useDashboardStats() {
  return useQuery({
    queryKey: ['admin-dashboard-stats'],
    queryFn: async () => {
      const { data } = await apiClient.get<DashboardStats>('/admin/dashboard/stats')
      return data
    },
  })
}

export type LeaderboardEntry = {
  id: number
  name: string
  municipality: string | null
  level: number
  xp: number
  streak_days: number
}

export function useXpLeaderboard() {
  return useQuery({
    queryKey: ['admin-xp-leaderboard'],
    queryFn: async () => {
      const { data } = await apiClient.get<{ leaderboard: LeaderboardEntry[] }>(
        '/admin/dashboard/xp-leaderboard'
      )
      return data.leaderboard
    },
  })
}
