import { useRef, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ImageUp, Loader2, RotateCcw } from 'lucide-react'
import { toast } from 'sonner'
import apiClient from '@/lib/api-client'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'

type Branding = { logo: string | null; logo_light: string | null; admin_logo: string | null; favicon: string | null }
type Variant = 'default' | 'light' | 'admin_logo' | 'favicon'

const API_ORIGIN = (import.meta.env.VITE_API_URL as string | undefined)?.replace(/\/api\/?$/, '') ?? ''

function LogoCard({
  variant,
  title,
  description,
  previewBg,
  currentUrl,
  accept = 'image/png,image/jpeg,image/webp',
  compact = false,
}: {
  variant: Variant
  title: string
  description: string
  previewBg: string
  currentUrl: string | null
  accept?: string
  compact?: boolean
}) {
  const qc = useQueryClient()
  const fileRef = useRef<HTMLInputElement>(null)
  const [busy, setBusy] = useState(false)

  const upload = async (file: File) => {
    setBusy(true)
    try {
      const form = new FormData()
      form.append('logo', file)
      form.append('variant', variant)
      await apiClient.post('/admin/branding/logo', form, { headers: { 'Content-Type': 'multipart/form-data' } })
      toast.success('Updated: takes effect on the next refresh.')
      qc.invalidateQueries({ queryKey: ['admin-branding'] })
    } catch (e: any) {
      toast.error(e?.response?.data?.message ?? 'Upload failed.')
    } finally {
      setBusy(false)
      if (fileRef.current) fileRef.current.value = ''
    }
  }

  const reset = useMutation({
    mutationFn: async () => apiClient.delete('/admin/branding/logo', { params: { variant } }),
    onSuccess: () => {
      toast.success('Back to the built-in default.')
      qc.invalidateQueries({ queryKey: ['admin-branding'] })
    },
    onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Could not reset.'),
  })

  return (
    <Card>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
        <CardDescription>{description}</CardDescription>
      </CardHeader>
      <CardContent className='space-y-4'>
        <div
          className={compact ? 'flex h-28 items-center justify-center rounded-md border' : 'flex h-28 items-center justify-center rounded-md border'}
          style={{ background: previewBg }}
        >
          {currentUrl ? (
            <img
              src={`${API_ORIGIN}${currentUrl}`}
              alt={title}
              className={compact ? 'size-10 object-contain' : 'max-h-20 max-w-[80%] object-contain'}
            />
          ) : (
            <span className={variant === 'light' ? 'text-2xl font-extrabold tracking-tight text-white' : 'text-2xl font-extrabold tracking-tight text-[#E7653B]'}>
              uLam <span className='align-super text-xs font-medium opacity-80'>(built-in)</span>
            </span>
          )}
        </div>

        <input
          ref={fileRef}
          type='file'
          accept={accept}
          className='hidden'
          onChange={(e) => {
            const f = e.target.files?.[0]
            if (f) upload(f)
          }}
        />
        <div className='flex gap-2'>
          <Button onClick={() => fileRef.current?.click()} disabled={busy}>
            {busy ? <Loader2 className='animate-spin' /> : <ImageUp />}
            Upload new
          </Button>
          {currentUrl && (
            <Button variant='outline' onClick={() => reset.mutate()} disabled={reset.isPending}>
              <RotateCcw /> Use built-in
            </Button>
          )}
        </div>
      </CardContent>
    </Card>
  )
}

export function BrandingPage() {
  const { data } = useQuery({
    queryKey: ['admin-branding'],
    queryFn: async () => (await apiClient.get<Branding>('/admin/branding')).data,
  })

  return (
    <>
      <Header fixed><Search className='me-auto' /><ThemeSwitch /><ConfigDrawer /><ProfileDropdown /></Header>
      <Main className='flex flex-1 flex-col gap-4 sm:gap-6'>
        <div>
          <h2 className='text-2xl font-bold tracking-tight'>Branding</h2>
          <p className='text-muted-foreground'>
            Replace the app logo anywhere it appears (welcome, login, home, and page headers), the admin
            dashboard's own logo, and its browser-tab favicon, all without an app update or rebuild.
            Leave a slot empty to keep the built-in default.
          </p>
        </div>

        <h3 className='text-sm font-semibold text-muted-foreground'>Mobile app</h3>
        <div className='grid gap-4 lg:grid-cols-2'>
          <LogoCard
            variant='default'
            title='Logo (light backgrounds)'
            description='Shown on cream/white screens: welcome, login, the Home header.'
            previewBg='#FFF8E8'
            currentUrl={data?.logo ?? null}
          />
          <LogoCard
            variant='light'
            title='Logo (colored headers)'
            description='The white/light version shown on terracotta headers: Profile, page headers, tab screens.'
            previewBg='linear-gradient(135deg,#CC5027,#E7653B)'
            currentUrl={data?.logo_light ?? null}
          />
        </div>

        <h3 className='mt-2 text-sm font-semibold text-muted-foreground'>Admin dashboard</h3>
        <div className='grid gap-4 lg:grid-cols-2'>
          <LogoCard
            variant='admin_logo'
            title='Admin logo'
            description="Shown in this dashboard’s sidebar and login page, separate from the mobile app logo above."
            previewBg='#FFF8E8'
            currentUrl={data?.admin_logo ?? null}
          />
          <LogoCard
            variant='favicon'
            title='Favicon'
            description='The browser-tab icon for this admin dashboard. Square images work best.'
            previewBg='#f4f4f5'
            currentUrl={data?.favicon ?? null}
            accept='image/png,image/x-icon,image/svg+xml'
            compact
          />
        </div>

        <p className='text-xs text-muted-foreground'>
          PNG with transparency recommended, up to 2 MB. Wide/horizontal images display best for the logo
          slots; the favicon should be roughly square.
        </p>
      </Main>
    </>
  )
}
