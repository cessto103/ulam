import { createFileRoute } from '@tanstack/react-router'
import { TindahanComments } from '@/features/tindahan-comments'

export const Route = createFileRoute('/_authenticated/tindahan-comments/')({
  component: TindahanComments,
})
