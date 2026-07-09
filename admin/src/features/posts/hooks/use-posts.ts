import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'
import { type Post } from '../data/schema'

type PostsSearch = {
  page?: number
  pageSize?: number
  search?: string
  post_type?: string[]
  is_sponsored?: string[]
  municipality?: string
}

type PaginatedResponse<T> = {
  data: T[]
  current_page: number
  last_page: number
  total: number
}

export function usePostsQuery(search: PostsSearch) {
  return useQuery({
    queryKey: ['admin-posts', search],
    queryFn: async () => {
      const { data } = await apiClient.get<PaginatedResponse<Post>>(
        '/admin/posts',
        {
          params: {
            page: search.page,
            per_page: search.pageSize,
            search: search.search || undefined,
            post_type: search.post_type?.[0],
            is_sponsored:
              search.is_sponsored && search.is_sponsored.length > 0
                ? true
                : undefined,
            municipality: search.municipality || undefined,
          },
        }
      )
      return data
    },
    placeholderData: (prev) => prev,
  })
}

export function useToggleSponsored() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, is_sponsored }: { id: number; is_sponsored: boolean }) =>
      apiClient.patch(`/admin/posts/${id}`, { is_sponsored }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-posts'] }),
  })
}

export function useDeletePost() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => apiClient.delete(`/admin/posts/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-posts'] }),
  })
}
