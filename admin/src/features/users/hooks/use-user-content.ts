import { useQuery } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'

export type UserPost = {
  id: number
  post_type: string
  body: string
  images: string[] | null
  puso_count: number
  dislike_count: number
  comments_count: number
  views_count: number
  created_at: string
}

export type UserRecipe = {
  id: number
  title: string
  image_url: string | null
  image_urls: string[] | null
  vote_up_count: number
  vote_down_count: number
  views_count: number
  is_published: boolean
  created_at: string
}

export type UserStore = {
  id: number
  name: string
  photo: string | null
  is_active: boolean
  is_verified: boolean
  items_count: number
  market: { id: number; name: string } | null
  created_at: string
}

export function useUserContentQuery(userId: number | null) {
  return useQuery({
    queryKey: ['admin-user-content', userId],
    queryFn: async () => {
      const { data } = await apiClient.get<{
        posts: UserPost[]
        recipes: UserRecipe[]
        stores: UserStore[]
      }>(`/admin/users/${userId}/content`)
      return data
    },
    enabled: userId != null,
  })
}
