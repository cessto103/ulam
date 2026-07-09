import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'
import { type PostComment } from '../data/schema'

type CommentsSearch = {
  page?: number
  pageSize?: number
  search?: string
  post_id?: number
  is_reply?: string[]
}

type PaginatedResponse<T> = {
  data: T[]
  current_page: number
  last_page: number
  total: number
}

export function useCommentsQuery(search: CommentsSearch) {
  return useQuery({
    queryKey: ['admin-comments', search],
    queryFn: async () => {
      const { data } = await apiClient.get<PaginatedResponse<PostComment>>(
        '/admin/comments',
        {
          params: {
            page: search.page,
            per_page: search.pageSize,
            search: search.search || undefined,
            post_id: search.post_id || undefined,
            is_reply: search.is_reply?.includes('reply')
              ? true
              : search.is_reply?.includes('top_level')
                ? false
                : undefined,
          },
        }
      )
      return data
    },
    placeholderData: (prev) => prev,
  })
}

export function useDeleteComment() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => apiClient.delete(`/admin/comments/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-comments'] }),
  })
}
