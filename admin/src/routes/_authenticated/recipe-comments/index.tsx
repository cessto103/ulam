import { createFileRoute } from '@tanstack/react-router'
import { RecipeComments } from '@/features/recipe-comments'

export const Route = createFileRoute('/_authenticated/recipe-comments/')({
  component: RecipeComments,
})
