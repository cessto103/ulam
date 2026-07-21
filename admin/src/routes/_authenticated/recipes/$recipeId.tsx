import { createFileRoute } from '@tanstack/react-router'
import { RecipeDetailPage } from '@/features/recipes/recipe-detail'

export const Route = createFileRoute('/_authenticated/recipes/$recipeId')({
  component: RecipeDetailPage,
})
