import { useQuery } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'

export type UserStrike = {
  id: number
  level: number
  level_label: string
  reason: string
  expires_at: string | null
  issued_by: number | null
  issuedBy: { id: number; name: string } | null
  created_at: string
}

export type UserContentReport = {
  id: number
  content_type: string
  content_id: number
  reason: string
  details: string | null
  status: string
  created_at: string
}

export type UserListingReport = {
  id: number
  reportable_type: string
  reportable_id: number
  reason: string
  status: string
  created_at: string
}

export type UserSupportTicket = {
  id: number
  subject: string
  category: string
  status: string
  last_reply_at: string | null
  created_at: string
}

export type UserModeration = {
  strikes: UserStrike[]
  content_reports_filed: UserContentReport[]
  content_reports_against: UserContentReport[]
  listing_reports_filed: UserListingReport[]
  support_tickets: UserSupportTicket[]
}

export function useUserModerationQuery(userId: number | null) {
  return useQuery({
    queryKey: ['admin-user-moderation', userId],
    queryFn: async () => {
      const { data } = await apiClient.get<UserModeration>(`/admin/users/${userId}/moderation`)
      return data
    },
    enabled: userId != null,
  })
}
