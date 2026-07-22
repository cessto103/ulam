import { useQuery } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'

export type PublicBranding = {
  logo: string | null
  logo_light: string | null
  admin_logo: string | null
  favicon: string | null
}

const API_ORIGIN = (import.meta.env.VITE_API_URL as string | undefined)?.replace(/\/api\/?$/, '') ?? ''

/** Unauthenticated — safe to call from the login page as well as the app shell. */
export function usePublicBranding() {
  return useQuery({
    queryKey: ['public-branding'],
    queryFn: async () => (await apiClient.get<PublicBranding>('/branding')).data,
    staleTime: 5 * 60_000,
  })
}

export function brandingAssetUrl(path: string | null): string | null {
  return path ? `${API_ORIGIN}${path}` : null
}
