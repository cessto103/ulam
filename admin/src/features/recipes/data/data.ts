export const sources = [
  { label: 'AI Generated', value: 'ai_generated' },
  { label: 'Community', value: 'community' },
  { label: 'Admin', value: 'admin' },
  { label: 'Official', value: 'official' },
] as const

export const budgetTags = [
  { label: '₱100', value: 'budget_100' },
  { label: '₱200', value: 'budget_200' },
  { label: '₱400', value: 'budget_400' },
  { label: '₱400+ (legacy)', value: 'budget_400plus' },
  { label: '₱600', value: 'budget_600' },
  { label: '₱800', value: 'budget_800' },
  { label: '₱1,000', value: 'budget_1000' },
  { label: '₱1,000+', value: 'budget_1000plus' },
] as const

export const difficulties = [
  { label: 'Madali', value: 'madali' },
  { label: 'Katamtaman', value: 'katamtaman' },
  { label: 'Mahirap', value: 'mahirap' },
] as const

export const publishedOptions = [
  { label: 'Published', value: 'published' },
  { label: 'Unpublished', value: 'unpublished' },
] as const
