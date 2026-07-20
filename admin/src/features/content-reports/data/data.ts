export const statuses = [
  { label: 'Pending', value: 'pending' },
  { label: 'Actioned', value: 'actioned' },
  { label: 'Dismissed', value: 'dismissed' },
] as const

export const contentTypes = [
  { label: 'Post', value: 'post' },
  { label: 'Recipe', value: 'recipe' },
  { label: 'Store', value: 'tindahan' },
] as const

export function contentTypeLabel(type: string): string {
  return contentTypes.find((t) => t.value === type)?.label ?? type
}

export const LEVEL_LABEL: Record<number, string> = {
  1: 'Warning',
  2: 'Restriction',
  3: 'Ban',
}
