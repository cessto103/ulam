import { useMutation, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'

export type RefundReason = 'duplicate' | 'fraudulent' | 'requested_by_customer' | 'others'

export function useRefundPayment() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async ({
      paymentId,
      amount,
      reason,
    }: {
      paymentId: number
      amount: number // centavos
      reason: RefundReason
    }) => {
      const { data } = await apiClient.post(
        `/admin/billing/payments/${paymentId}/refund`,
        { amount, reason }
      )
      return data
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-payments'] }),
  })
}
