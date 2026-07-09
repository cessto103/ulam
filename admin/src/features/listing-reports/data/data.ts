export const statuses = [
  { label: 'Pending', value: 'pending' },
  { label: 'Actioned', value: 'actioned' },
  { label: 'Dismissed', value: 'dismissed' },
] as const

export const reportableTypes = [
  { label: 'Market', value: 'market' },
  { label: 'Store/Stall', value: 'tindahan' },
] as const

// `reportable_type` on the wire is the full PHP class name (e.g. "App\\Models\\Tindahan").
export function reportableTypeLabel(reportableType: string): string {
  if (reportableType.includes('Tindahan')) return 'Store/Stall'
  if (reportableType.includes('Market')) return 'Market'
  return reportableType
}
