import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'

export type RecipeCommentRow = {
  id: number
  recipe_id: number
  user_id: number
  parent_id: number | null
  body: string
  created_at: string
  user: { id: number; name: string; username: string; avatar: string | null } | null
  recipe: { id: number; title: string } | null
}

type Page<T> = { data: T[]; current_page: number; last_page: number; total: number }

export function useRecipeComments(params: { page: number; search?: string }) {
  return useQuery({
    queryKey: ['admin-recipe-comments', params],
    queryFn: async () =>
      (await apiClient.get<Page<RecipeCommentRow>>('/admin/recipe-comments', { params })).data,
    placeholderData: (previous) => previous,
  })
}

export function useDeleteRecipeComment() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => apiClient.delete(`/admin/recipe-comments/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-recipe-comments'] }),
  })
}
