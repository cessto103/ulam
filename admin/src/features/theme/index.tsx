import { useRef, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ImageUp, Loader2, RotateCcw } from 'lucide-react'
import { toast } from 'sonner'
import apiClient from '@/lib/api-client'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'

type SectionConfig = {
  image?: string | null
  focal_x?: number
  focal_y?: number
  fit?: 'cover' | 'contain'
  overlay_colors?: string[]
  overlay_opacity?: number
}
type ThemeResponse = { sections: Record<string, SectionConfig> }

const API_ORIGIN = (import.meta.env.VITE_API_URL as string | undefined)?.replace(/\/api\/?$/, '') ?? ''

const FOCAL_POINTS = [
  { x: 0, y: 0 }, { x: 50, y: 0 }, { x: 100, y: 0 },
  { x: 0, y: 50 }, { x: 50, y: 50 }, { x: 100, y: 50 },
  { x: 0, y: 100 }, { x: 50, y: 100 }, { x: 100, y: 100 },
]

function ImageSectionCard({
  sectionKey,
  title,
  description,
  defaultColors,
  cfg,
}: {
  sectionKey: string
  title: string
  description: string
  defaultColors: string[]
  cfg?: SectionConfig
}) {
  const qc = useQueryClient()
  const fileRef = useRef<HTMLInputElement>(null)
  const [busy, setBusy] = useState(false)

  const focalX = cfg?.focal_x ?? 50
  const focalY = cfg?.focal_y ?? 50
  const fit = cfg?.fit ?? 'cover'
  const colors = cfg?.overlay_colors?.length ? cfg.overlay_colors : defaultColors
  const opacity = cfg?.overlay_opacity ?? 1

  const invalidate = () => qc.invalidateQueries({ queryKey: ['admin-theme'] })

  const upload = async (file: File) => {
    setBusy(true)
    try {
      const form = new FormData()
      form.append('image', file)
      await apiClient.post(`/admin/theme/${sectionKey}/image`, form, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      toast.success('Background updated — the app picks it up on its next refresh.')
      invalidate()
    } catch (e: any) {
      toast.error(e?.response?.data?.message ?? 'Upload failed.')
    } finally {
      setBusy(false)
      if (fileRef.current) fileRef.current.value = ''
    }
  }

  const updateSettings = useMutation({
    mutationFn: async (patch: Partial<SectionConfig>) => apiClient.patch(`/admin/theme/${sectionKey}`, patch),
    onSuccess: invalidate,
    onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Could not save.'),
  })

  const reset = useMutation({
    mutationFn: async () => apiClient.delete(`/admin/theme/${sectionKey}`),
    onSuccess: () => {
      toast.success('Back to the built-in look.')
      invalidate()
    },
    onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Could not reset.'),
  })

  const setColor = (index: number, value: string) => {
    const next = [...colors]
    next[index] = value
    updateSettings.mutate({ overlay_colors: next })
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className='text-base'>{title}</CardTitle>
        {description && <CardDescription>{description}</CardDescription>}
      </CardHeader>
      <CardContent className='space-y-4'>
        <div
          className='relative flex h-28 items-center justify-center overflow-hidden rounded-md border bg-cover'
          style={{
            backgroundImage: cfg?.image ? `url(${API_ORIGIN}${cfg.image})` : undefined,
            backgroundPosition: `${focalX}% ${focalY}%`,
            backgroundColor: !cfg?.image ? '#f3f3f3' : undefined,
          }}
        >
          <div
            className='absolute inset-0'
            style={{
              background: colors.length >= 2 ? `linear-gradient(135deg, ${colors[0]}, ${colors[1]})` : colors[0],
              opacity,
            }}
          />
          {!cfg?.image && <span className='z-10 text-xs text-muted-foreground'>Using built-in photo</span>}
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
        <div className='flex flex-wrap gap-2'>
          <Button size='sm' onClick={() => fileRef.current?.click()} disabled={busy}>
            {busy ? <Loader2 className='animate-spin' /> : <ImageUp />} Upload photo
          </Button>
          {(cfg?.image || cfg?.overlay_colors || cfg?.focal_x !== undefined) && (
            <Button size='sm' variant='outline' onClick={() => reset.mutate()} disabled={reset.isPending}>
              <RotateCcw /> Reset section
            </Button>
          )}
        </div>

        <div>
          <Label className='mb-1.5 block text-xs'>Focal point</Label>
          <div className='grid w-24 grid-cols-3 gap-1'>
            {FOCAL_POINTS.map((p) => (
              <button
                key={`${p.x}-${p.y}`}
                type='button'
                onClick={() => updateSettings.mutate({ focal_x: p.x, focal_y: p.y })}
                className={`h-7 w-7 rounded border ${
                  focalX === p.x && focalY === p.y ? 'border-primary bg-primary/20' : 'border-muted-foreground/30'
                }`}
              />
            ))}
          </div>
        </div>

        <div className='flex items-center gap-2'>
          <Label className='text-xs'>Fit</Label>
          <Button size='sm' variant={fit === 'cover' ? 'default' : 'outline'} onClick={() => updateSettings.mutate({ fit: 'cover' })}>
            Cover
          </Button>
          <Button size='sm' variant={fit === 'contain' ? 'default' : 'outline'} onClick={() => updateSettings.mutate({ fit: 'contain' })}>
            Contain
          </Button>
        </div>

        <div className='space-y-2'>
          <Label className='text-xs'>Overlay color{colors.length > 1 ? 's' : ''}</Label>
          <div className='flex flex-wrap gap-2'>
            {colors.map((c, i) => (
              <div key={i} className='flex items-center gap-1'>
                <input type='color' value={c} onChange={(e) => setColor(i, e.target.value)} className='h-8 w-8 rounded border' />
                <Input value={c} onChange={(e) => setColor(i, e.target.value)} className='h-8 w-24 text-xs' />
              </div>
            ))}
          </div>
          <div className='flex items-center gap-2'>
            <Label className='text-xs'>Opacity</Label>
            <input
              type='range'
              min={0}
              max={1}
              step={0.05}
              value={opacity}
              onChange={(e) => updateSettings.mutate({ overlay_opacity: Number(e.target.value) })}
              className='w-32'
            />
            <span className='text-xs text-muted-foreground'>{Math.round(opacity * 100)}%</span>
          </div>
        </div>
      </CardContent>
    </Card>
  )
}

function ColorOnlyCard({
  sectionKey,
  title,
  defaultBg,
  defaultText,
  cfg,
}: {
  sectionKey: string
  title: string
  defaultBg: string
  defaultText: string
  cfg?: SectionConfig
}) {
  const qc = useQueryClient()
  const colors = cfg?.overlay_colors?.length === 2 ? cfg.overlay_colors : [defaultBg, defaultText]

  const updateSettings = useMutation({
    mutationFn: async (patch: Partial<SectionConfig>) => apiClient.patch(`/admin/theme/${sectionKey}`, patch),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-theme'] }),
    onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Could not save.'),
  })
  const reset = useMutation({
    mutationFn: async () => apiClient.delete(`/admin/theme/${sectionKey}`),
    onSuccess: () => {
      toast.success('Back to default colors.')
      qc.invalidateQueries({ queryKey: ['admin-theme'] })
    },
  })

  const setColor = (index: 0 | 1, value: string) => {
    const next = [...colors]
    next[index] = value
    updateSettings.mutate({ overlay_colors: next })
  }

  return (
    <Card>
      <CardHeader className='pb-3'>
        <CardTitle className='text-sm'>{title}</CardTitle>
      </CardHeader>
      <CardContent className='space-y-3'>
        <div
          className='flex h-14 items-center justify-center rounded-md text-sm font-semibold'
          style={{ backgroundColor: colors[0], color: colors[1] }}
        >
          {title}
        </div>
        <div className='flex items-center gap-3'>
          <div className='flex items-center gap-1'>
            <Label className='text-xs'>Box</Label>
            <input type='color' value={colors[0]} onChange={(e) => setColor(0, e.target.value)} className='h-7 w-7 rounded border' />
          </div>
          <div className='flex items-center gap-1'>
            <Label className='text-xs'>Text</Label>
            <input type='color' value={colors[1]} onChange={(e) => setColor(1, e.target.value)} className='h-7 w-7 rounded border' />
          </div>
          {cfg?.overlay_colors && (
            <Button size='sm' variant='ghost' onClick={() => reset.mutate()}>
              <RotateCcw className='size-3.5' />
            </Button>
          )}
        </div>
      </CardContent>
    </Card>
  )
}

export function ThemePage() {
  const { data } = useQuery({
    queryKey: ['admin-theme'],
    queryFn: async () => (await apiClient.get<ThemeResponse>('/admin/theme')).data,
  })
  const sections = data?.sections ?? {}

  return (
    <>
      <Header fixed>
        <Search className='me-auto' />
        <ThemeSwitch />
        <ConfigDrawer />
        <ProfileDropdown />
      </Header>
      <Main className='flex flex-1 flex-col gap-6'>
        <div>
          <h2 className='text-2xl font-bold tracking-tight'>Theme</h2>
          <p className='text-muted-foreground'>
            Control the background photo, crop focus, and color overlay for the page header and Home dashboard
            boxes, plus the Awards stat colors. Leave a section untouched to keep the built-in look.
          </p>
        </div>

        <div className='grid gap-4 lg:grid-cols-2 xl:grid-cols-3'>
          <ImageSectionCard
            sectionKey='header'
            title='Page header'
            description='Menu Plan, Community, and Prices page headers.'
            defaultColors={['#CC5027', '#EC8156']}
            cfg={sections.header}
          />
          <ImageSectionCard
            sectionKey='dashboard_meal_plan'
            title='Home — Meal Plan card'
            description='The big hero card on Home.'
            defaultColors={['#2C341E', '#E7653B']}
            cfg={sections.dashboard_meal_plan}
          />
          <ImageSectionCard
            sectionKey='dashboard_my_recipes'
            title='Home — My Recipes tile'
            description=''
            defaultColors={['#C45E3A']}
            cfg={sections.dashboard_my_recipes}
          />
          <ImageSectionCard
            sectionKey='dashboard_spending_history'
            title='Home — Spending History tile'
            description=''
            defaultColors={['#E3A32A']}
            cfg={sections.dashboard_spending_history}
          />
          <ImageSectionCard
            sectionKey='dashboard_awards'
            title='Home — Awards tile'
            description=''
            defaultColors={['#386641']}
            cfg={sections.dashboard_awards}
          />
          <ImageSectionCard
            sectionKey='dashboard_recipe_book'
            title='Home — Recipe Book tile'
            description=''
            defaultColors={['#3C3A2F']}
            cfg={sections.dashboard_recipe_book}
          />
        </div>

        <div>
          <h3 className='mb-3 text-lg font-semibold'>Awards — "Your stats" colors</h3>
          <div className='grid gap-3 sm:grid-cols-2 lg:grid-cols-4'>
            <ColorOnlyCard sectionKey='awards_stat_saved' title='Saved' defaultBg='#F4B942' defaultText='#58200F' cfg={sections.awards_stat_saved} />
            <ColorOnlyCard sectionKey='awards_stat_meal_plans' title='Meal Plans' defaultBg='#386641' defaultText='#FFFFFF' cfg={sections.awards_stat_meal_plans} />
            <ColorOnlyCard sectionKey='awards_stat_posts' title='Posts' defaultBg='#E7653B' defaultText='#FFFFFF' cfg={sections.awards_stat_posts} />
            <ColorOnlyCard sectionKey='awards_stat_achievements' title='Achievements' defaultBg='#5E693F' defaultText='#FFFFFF' cfg={sections.awards_stat_achievements} />
          </div>
        </div>
      </Main>
    </>
  )
}
