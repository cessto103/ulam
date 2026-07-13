import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'

export type TicketMessage = {
  id: number
  support_ticket_id: number
  sender_id: number | null
  is_from_admin: boolean
  body: string
  created_at: string
  sender?: { id: number; name: string; avatar: string | null } | null
}

export type SupportTicket = {
  id: number
  user_id: number
  subject: string
  category: string
  status: 'open' | 'answered' | 'closed'
  last_reply_at: string | null
  created_at: string
  user: { id: number; name: string; username: string; email: string } | null
  latest_message?: TicketMessage | null
  messages?: TicketMessage[]
}

type TicketsResponse = {
  data: SupportTicket[]
  current_page: number
  last_page: number
  total: number
  counts: { open: number }
}

const QUERY_KEY = 'admin-support-tickets'

export function useTicketsQuery(params: {
  page: number
  status?: string
  search?: string
}) {
  return useQuery({
    queryKey: [QUERY_KEY, params],
    queryFn: async () => {
      const { data } = await apiClient.get<TicketsResponse>(
        '/admin/support-tickets',
        {
          params: {
            page: params.page,
            status: params.status || undefined,
            search: params.search || undefined,
          },
        }
      )
      return data
    },
    placeholderData: (prev) => prev,
  })
}

export function useTicketQuery(id: number | null) {
  return useQuery({
    queryKey: [QUERY_KEY, 'detail', id],
    enabled: id !== null,
    queryFn: async () => {
      const { data } = await apiClient.get<{ ticket: SupportTicket }>(
        `/admin/support-tickets/${id}`
      )
      return data.ticket
    },
  })
}

export function useReplyToTicket() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async ({ id, body }: { id: number; body: string }) => {
      const { data } = await apiClient.post(
        `/admin/support-tickets/${id}/reply`,
        { body }
      )
      return data
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: [QUERY_KEY] }),
  })
}

export function useCloseTicket() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (id: number) => {
      const { data } = await apiClient.post(`/admin/support-tickets/${id}/close`)
      return data
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: [QUERY_KEY] }),
  })
}
