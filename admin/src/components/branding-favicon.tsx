import { useEffect } from 'react'
import { brandingAssetUrl, usePublicBranding } from '@/hooks/use-public-branding'

/**
 * Swaps the browser-tab favicon at runtime when a custom one is set in
 * Branding — the static <link> tags in index.html are baked in at build
 * time, so this is the only way an uploaded favicon takes effect without a
 * full admin rebuild+redeploy. Renders nothing.
 */
export function BrandingFavicon() {
  const { data } = usePublicBranding()
  const url = brandingAssetUrl(data?.favicon ?? null)

  useEffect(() => {
    if (!url) return

    const existing = Array.from(document.querySelectorAll("link[rel='icon']"))
    existing.forEach((el) => el.remove())

    const link = document.createElement('link')
    link.rel = 'icon'
    link.href = url
    document.head.appendChild(link)

    return () => {
      link.remove()
    }
  }, [url])

  return null
}
