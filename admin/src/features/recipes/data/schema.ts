import { z } from 'zod'

export const recipeSources = [
  'ai_generated',
  'community',
  'admin',
  'official',
] as const

export const recipeBudgetTags = [
  'budget_100',
  'budget_200',
  'budget_400',
  'budget_400plus', // legacy tag from an older tier scheme -- no longer assignable to new recipes, but old ones still carry it
  'budget_600',
  'budget_800',
  'budget_1000',
  'budget_1000plus',
] as const

export const recipeDifficulties = ['madali', 'katamtaman', 'mahirap'] as const

const recipeUserSchema = z.object({
  id: z.number(),
  name: z.string(),
})

const recipeUserDetailSchema = z.object({
  id: z.number(),
  name: z.string(),
  username: z.string().nullable(),
  avatar: z.string().nullable(),
  barangay: z.string().nullable(),
  municipality: z.string().nullable(),
})

const recipeCommentAuthorSchema = z.object({
  id: z.number(),
  name: z.string(),
  username: z.string().nullable(),
  avatar: z.string().nullable(),
})

export type RecipeCommentDetail = {
  id: number
  body: string
  created_at: string
  user: z.infer<typeof recipeCommentAuthorSchema> | null
  replies: RecipeCommentDetail[]
}

export const recipeSchema = z.object({
  id: z.number(),
  user_id: z.number().nullable(),
  user: recipeUserSchema.nullable(),
  title: z.string(),
  description: z.string().nullable(),
  category: z.string().nullable(),
  source: z.enum(recipeSources),
  budget_tag: z.enum(recipeBudgetTags),
  estimated_cost: z.string().nullable(),
  servings: z.number().nullable(),
  prep_time_minutes: z.number().nullable(),
  cook_time_minutes: z.number().nullable(),
  difficulty: z.string().nullable(),
  steps: z.array(z.string()).nullable(),
  tips: z.array(z.string()).nullable(),
  tags: z.array(z.string()).nullable(),
  dietary_flags: z.array(z.string()).nullable(),
  image_url: z.string().nullable(),
  image_urls: z.array(z.string()).nullable(),
  youtube_url: z.string().nullable(),
  collage_style: z.string().nullable(),
  gradient_key: z.string().nullable(),
  font_key: z.string().nullable(),
  is_published: z.boolean(),
  is_premium_only: z.boolean(),
  save_count: z.number(),
  share_count: z.number(),
  vote_up_count: z.number().optional(),
  vote_down_count: z.number().optional(),
  views_count: z.number().optional(),
  average_rating: z.string(),
  ratings_count: z.number(),
  created_at: z.string(),
  updated_at: z.string(),
})
export type Recipe = z.infer<typeof recipeSchema>

export type RecipeDetail = Recipe & {
  user: z.infer<typeof recipeUserDetailSchema> | null
  comments: RecipeCommentDetail[]
  ingredients: RecipeIngredient[]
}

export const recipeIngredientSchema = z.object({
  id: z.number(),
  recipe_id: z.number(),
  name: z.string(),
  quantity: z.string().nullable(),
  unit: z.string().nullable(),
  estimated_price: z.string().nullable(),
  notes: z.string().nullable(),
  sort_order: z.number(),
  created_at: z.string(),
  updated_at: z.string(),
})
export type RecipeIngredient = z.infer<typeof recipeIngredientSchema>
