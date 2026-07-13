import { createFileRoute } from '@tanstack/react-router'
import { SellerSubscriptions } from '@/features/seller-subscriptions'

export const Route = createFileRoute('/_authenticated/seller-subscriptions/')({
  component: SellerSubscriptions,
})
