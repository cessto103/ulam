export const availabilityOptions = [
  { label: 'Available', value: 'available' },
  { label: 'Unavailable', value: 'unavailable' },
] as const

// Common categories seen in the field — a reasonable preset list, not a
// strict enum. The search box can always find other category values.
export const categoryOptions = [
  { label: 'Isda', value: 'isda' },
  { label: 'Karne', value: 'karne' },
  { label: 'Gulay', value: 'gulay' },
  { label: 'Bigas', value: 'bigas' },
  { label: 'Prutas', value: 'prutas' },
  { label: 'Sangkap', value: 'sangkap' },
] as const
