import { createFileRoute } from '@tanstack/react-router'
import { DailyTasks } from '@/features/daily-tasks'

export const Route = createFileRoute('/_authenticated/daily-tasks/')({
  component: DailyTasks,
})
