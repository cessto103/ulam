import { createFileRoute } from '@tanstack/react-router'
import { EmailTemplates } from '@/features/email-templates'

export const Route = createFileRoute('/_authenticated/email-templates/')({
  component: EmailTemplates,
})
