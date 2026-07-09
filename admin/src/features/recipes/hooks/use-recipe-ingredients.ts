import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'
import { type RecipeIngredient } from '../data/schema'

export function useRecipeIngredientsQuery(recipeId: number | undefined) {
  return useQuery({
    queryKey: ['admin-recipe-ingredients', recipeId],
    queryFn: async () => {
      const { data } = await apiClient.get<{
        ingredients: RecipeIngredient[]
      }>(`/admin/recipes/${recipeId}/ingredients`)
      return data.ingredients
    },
    enabled: !!recipeId,
  })
}

type IngredientInput = {
  name?: string
  quantity?: string | null
  unit?: string | null
  estimated_price?: number | null
  notes?: string | null
  sort_order?: number
}

export function useCreateRecipeIngredient(recipeId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: IngredientInput) =>
      apiClient.post(`/admin/recipes/${recipeId}/ingredients`, input),
    onSuccess: () => {
      qc.invalidateQueries({
        queryKey: ['admin-recipe-ingredients', recipeId],
      })
      qc.invalidateQueries({ queryKey: ['admin-recipes'] })
    },
  })
}

type UpdateIngredientInput = IngredientInput & { id: number }

export function useUpdateRecipeIngredient(recipeId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, ...input }: UpdateIngredientInput) =>
      apiClient.patch(`/admin/recipes/${recipeId}/ingredients/${id}`, input),
    onSuccess: () => {
      qc.invalidateQueries({
        queryKey: ['admin-recipe-ingredients', recipeId],
      })
      qc.invalidateQueries({ queryKey: ['admin-recipes'] })
    },
  })
}

export function useDeleteRecipeIngredient(recipeId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) =>
      apiClient.delete(`/admin/recipes/${recipeId}/ingredients/${id}`),
    onSuccess: () => {
      qc.invalidateQueries({
        queryKey: ['admin-recipe-ingredients', recipeId],
      })
      qc.invalidateQueries({ queryKey: ['admin-recipes'] })
    },
  })
}
