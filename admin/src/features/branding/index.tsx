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

type Branding = { logo: string | null; logo_light: string | null }

const API_ORIGIN = (import.meta.env.VITE_API_URL as string | undefined)?.replace(/\/api\/?$/, '') ?? ''

function LogoCard({
  variant,
  title,
  description,
  previewBg,
  currentUrl,
}: {
  variant: 'default' | 'light'
  title: string
  description: string
  previewBg: string
  currentUrl: string | null
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
      toast.success('Logo updated: the app picks it up on its next refresh.')
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
      toast.success('Back to the built-in uLam logo.')
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
          className='flex h-28 items-center justify-center rounded-md border'
          style={{ background: previewBg }}
        >
          {currentUrl ? (
            <img src={`${API_ORIGIN}${currentUrl}`} alt={title} className='max-h-20 max-w-[80%] object-contain' />
          ) : (
            <span className={variant === 'light' ? 'text-2xl font-extrabold tracking-tight text-white' : 'text-2xl font-extrabold tracking-tight text-[#E7653B]'}>
              uLam <span className='align-super text-xs font-medium opacity-80'>(built-in)</span>
            </span>
          )}
        </div>

        <input
          ref={fileRef}
          type='file'
          accept='image/png,image/jpeg,image/webp'
          className='hidden'
          onChange={(e) => {
            const f = e.target.files?.[0]
            if (f) upload(f)
          }}
        />
        <div className='flex gap-2'>
          <Button onClick={() => fileRef.current?.click()} disabled={busy}>
            {busy ? <Loader2 className='animate-spin' /> : <ImageUp />}
            Upload new logo
          </Button>
          {currentUrl && (
            <Button variant='outline' onClick={() => reset.mutate()} disabled={reset.isPending}>
              <RotateCcw /> Use built-in
            </Button>
          )}
        </div>
        <p className='text-xs text-muted-foreground'>
          PNG with transparency recommended, up to 2 MB. Wide/horizontal logos display best.
        </p>
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
            Replace the app logo anywhere it appears (welcome, login, home, and page headers) without an app update.
            Leave a slot empty to keep the built-in uLam script logo.
          </p>
        </div>
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
      </Main>
    </>
  )
}
