import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'
import { type Recipe } from '../data/schema'

type RecipesSearch = {
  page?: number
  pageSize?: number
  search?: string
  source?: string[]
  budget_tag?: string[]
  is_published?: string[]
}

type PaginatedResponse<T> = {
  data: T[]
  current_page: number
  last_page: number
  total: number
}

export function useRecipesQuery(search: RecipesSearch) {
  return useQuery({
    queryKey: ['admin-recipes', search],
    queryFn: async () => {
      const { data } = await apiClient.get<PaginatedResponse<Recipe>>(
        '/admin/recipes',
        {
          params: {
            page: search.page,
            per_page: search.pageSize,
            search: search.search || undefined,
            source: search.source?.[0],
            budget_tag: search.budget_tag?.[0],
            is_published:
              search.is_published && search.is_published.length > 0
                ? search.is_published[0] === 'published'
                : undefined,
          },
        }
      )
      return data
    },
    placeholderData: (prev) => prev,
  })
}

export type RecipeInput = {
  user_id?: number | null
  title?: string
  description?: string | null
  category?: string | null
  source?: string
  budget_tag?: string
  estimated_cost?: number | null
  servings?: number | null
  prep_time_minutes?: number | null
  cook_time_minutes?: number | null
  difficulty?: string | null
  steps?: string[]
  tips?: string[]
  tags?: string[]
  dietary_flags?: string[]
  image_url?: string | null
  image_urls?: string[]
  youtube_url?: string | null
  collage_style?: string | null
  gradient_key?: string | null
  font_key?: string | null
  is_published?: boolean
  is_premium_only?: boolean
}

export function useCreateRecipe() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: RecipeInput) =>
      apiClient.post('/admin/recipes', input),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-recipes'] }),
  })
}

type UpdateRecipeInput = RecipeInput & { id: number }

export function useUpdateRecipe() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, ...input }: UpdateRecipeInput) =>
      apiClient.patch(`/admin/recipes/${id}`, input),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-recipes'] }),
  })
}

export function useDeleteRecipe() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => apiClient.delete(`/admin/recipes/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-recipes'] }),
  })
}

export function useTogglePublishRecipe() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, is_published }: { id: number; is_published: boolean }) =>
      apiClient.patch(`/admin/recipes/${id}`, { is_published }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-recipes'] }),
  })
}
