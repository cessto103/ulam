import { type SVGProps } from 'react'
import { Logo } from '@/assets/logo'
import { brandingAssetUrl, usePublicBranding } from '@/hooks/use-public-branding'

/**
 * The admin dashboard's own sidebar/login mark — separate from the mobile
 * app's logo. Falls back to the built-in uLam SVG mark when no custom admin
 * logo is set. Accepts the same `className` prop as `Logo` so it drops into
 * every existing call site (team-switcher, auth-layout) unchanged.
 */
export function AdminLogo(props: SVGProps<SVGSVGElement>) {
  const { data } = usePublicBranding()
  const url = brandingAssetUrl(data?.admin_logo ?? null)

  if (url) {
    return <img src={url} className={props.className} alt='uLam' style={{ objectFit: 'contain' }} />
  }

  return <Logo {...props} />
}
