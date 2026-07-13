import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'

export type TindahanCommentRow = {
  id: number
  tindahan_id: number
  user_id: number
  parent_id: number | null
  body: string
  created_at: string
  user: { id: number; name: string; username: string; avatar: string | null } | null
  tindahan: { id: number; name: string } | null
}

export type TindahanRatingRow = {
  id: number
  tindahan_id: number
  user_id: number
  rating: number
  created_at: string
  user: { id: number; name: string; username: string; avatar: string | null } | null
  tindahan: { id: number; name: string } | null
}

type Page<T> = { data: T[]; current_page: number; last_page: number; total: number }

export function useTindahanComments(params: { page: number; search?: string }) {
  return useQuery({
    queryKey: ['admin-tindahan-comments', params],
    queryFn: async () =>
      (await apiClient.get<Page<TindahanCommentRow>>('/admin/tindahan-comments', { params })).data,
    placeholderData: (previous) => previous,
  })
}

export function useDeleteTindahanComment() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => apiClient.delete(`/admin/tindahan-comments/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-tindahan-comments'] }),
  })
}

export function useTindahanRatings(params: { page: number }) {
  return useQuery({
    queryKey: ['admin-tindahan-ratings', params],
    queryFn: async () =>
      (await apiClient.get<Page<TindahanRatingRow>>('/admin/tindahan-ratings', { params })).data,
    placeholderData: (previous) => previous,
  })
}

export function useDeleteTindahanRating() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => apiClient.delete(`/admin/tindahan-ratings/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-tindahan-ratings'] }),
  })
}
