import { Award, ChefHat, MessageSquareText, Tag } from 'lucide-react'

export const postTypes = [
  { label: 'Recipe Share', value: 'recipe_share', icon: ChefHat },
  { label: 'Price Tip', value: 'price_tip', icon: Tag },
  { label: 'Budget Win', value: 'budget_win', icon: Award },
  { label: 'General', value: 'general', icon: MessageSquareText },
] as const
