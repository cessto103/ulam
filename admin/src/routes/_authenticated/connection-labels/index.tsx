import { createFileRoute } from '@tanstack/react-router'
import { ConnectionLabels } from '@/features/connection-labels'

export const Route = createFileRoute('/_authenticated/connection-labels/')({
  component: ConnectionLabels,
})
