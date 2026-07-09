// Mirrors CommunityPriceReportResource::CATEGORIES in the Laravel Filament admin.
export const categories = [
  { label: 'Isda', value: 'isda' },
  { label: 'Karne', value: 'karne' },
  { label: 'Gulay', value: 'gulay' },
  { label: 'Bigas', value: 'bigas' },
  { label: 'Prutas', value: 'prutas' },
  { label: 'Sangkap', value: 'sangkap' },
  { label: 'Itlog', value: 'itlog' },
  { label: 'Manok', value: 'manok' },
  { label: 'Baboy', value: 'baboy' },
  { label: 'Baka', value: 'baka' },
  { label: 'Iba pa', value: 'iba pa' },
] as const

export const verifiedOptions = [
  { label: 'Verified', value: 'true' },
  { label: 'Unverified', value: 'false' },
] as const
