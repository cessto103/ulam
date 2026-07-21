import { createFileRoute } from '@tanstack/react-router'
import { BusinessSettingsPage } from '@/features/business-settings'

export const Route = createFileRoute('/_authenticated/business-settings/')({
  component: BusinessSettingsPage,
})
