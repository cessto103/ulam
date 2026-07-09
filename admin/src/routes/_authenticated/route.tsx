import { createFileRoute, redirect } from '@tanstack/react-router'
import apiClient from '@/lib/api-client'
import { useAuthStore, type AuthUser } from '@/stores/auth-store'
import { AuthenticatedLayout } from '@/components/layout/authenticated-layout'

export const Route = createFileRoute('/_authenticated')({
  beforeLoad: async ({ context, location }) => {
    const { auth } = useAuthStore.getState()

    if (!auth.accessToken) {
      throw redirect({
        to: '/sign-in',
        search: { redirect: location.href },
      })
    }

    if (!auth.user) {
      try {
        const user = await context.queryClient.ensureQueryData({
          queryKey: ['admin-me'],
          queryFn: async () => {
            const { data } = await apiClient.get<{ user: AuthUser }>('/admin/me')
            return data.user
          },
        })
        auth.setUser(user)
      } catch {
        auth.reset()
        throw redirect({
          to: '/sign-in',
          search: { redirect: location.href },
        })
      }
    }
  },
  component: AuthenticatedLayout,
})
