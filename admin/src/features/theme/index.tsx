import { useEffect, useRef, useState } from 'react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { Check, Copy, ImageUp, Loader2, Pencil, Plus, RotateCcw, Trash2 } from 'lucide-react'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import {
  type SectionConfig,
  type ThemePreset,
  useActivatePreset,
  useCreatePreset,
  useDeletePreset,
  usePresetsQuery,
  useRenamePreset,
  useResetSection,
  useUpdateSection,
  useUploadSectionImage,
} from './hooks/use-theme'

const API_ORIGIN = (import.meta.env.VITE_API_URL as string | undefined)?.replace(/\/api\/?$/, '') ?? ''

const FOCAL_POINTS = [
  { x: 0, y: 0 }, { x: 50, y: 0 }, { x: 100, y: 0 },
  { x: 0, y: 50 }, { x: 50, y: 50 }, { x: 100, y: 50 },
  { x: 0, y: 100 }, { x: 50, y: 100 }, { x: 100, y: 100 },
]

function PresetGallery({
  presets,
  selectedId,
  onSelect,
}: {
  presets: ThemePreset[]
  selectedId: number | null
  onSelect: (id: number) => void
}) {
  const createPreset = useCreatePreset()
  const renamePreset = useRenamePreset()
  const activatePreset = useActivatePreset()
  const deletePreset = useDeletePreset()

  const [createOpen, setCreateOpen] = useState(false)
  const [createName, setCreateName] = useState('')
  const [duplicateFrom, setDuplicateFrom] = useState<ThemePreset | null>(null)

  const [renameTarget, setRenameTarget] = useState<ThemePreset | null>(null)
  const [renameValue, setRenameValue] = useState('')

  const [deleteTarget, setDeleteTarget] = useState<ThemePreset | null>(null)

  const openCreate = (from?: ThemePreset) => {
    setDuplicateFrom(from ?? null)
    setCreateName(from ? `${from.name} copy` : '')
    setCreateOpen(true)
  }

  return (
    <div>
      <div className='mb-3 flex items-center justify-between'>
        <h3 className='text-lg font-semibold'>Presets</h3>
        <Button size='sm' onClick={() => openCreate()}>
          <Plus /> New preset
        </Button>
      </div>

      <div className='mb-2 flex flex-wrap gap-2'>
        {presets.map((p) => (
          <div
            key={p.id}
            className={`flex items-center gap-0.5 rounded-full border py-1 pl-1 pr-1 ${
              selectedId === p.id ? 'border-primary bg-primary/5' : 'border-border'
            }`}
          >
            <button
              type='button'
              onClick={() => onSelect(p.id)}
              className='rounded-full px-3 py-1 text-sm font-medium'
            >
              {p.name}
            </button>
            {p.is_active && (
              <span className='mr-1 rounded-full bg-leaf-100 px-2 py-0.5 text-xs font-semibold text-leaf-700' style={{ background: '#EFF4EC', color: '#386641' }}>
                Active
              </span>
            )}
            {!p.is_active && (
              <Button
                size='icon'
                variant='ghost'
                className='size-7'
                title='Make this the live theme'
                disabled={activatePreset.isPending}
                onClick={() =>
                  activatePreset.mutate(p.id, {
                    onSuccess: () => toast.success(`"${p.name}" is now live in the app.`),
                    onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Could not activate.'),
                  })
                }
              >
                <Check className='size-3.5' />
              </Button>
            )}
            <Button size='icon' variant='ghost' className='size-7' title='Duplicate' onClick={() => openCreate(p)}>
              <Copy className='size-3.5' />
            </Button>
            <Button
              size='icon'
              variant='ghost'
              className='size-7'
              title='Rename'
              onClick={() => { setRenameTarget(p); setRenameValue(p.name) }}
            >
              <Pencil className='size-3.5' />
            </Button>
            {!p.is_active && (
              <Button size='icon' variant='ghost' className='size-7' title='Delete' onClick={() => setDeleteTarget(p)}>
                <Trash2 className='size-3.5 text-red-500' />
              </Button>
            )}
          </div>
        ))}
      </div>

      <Dialog open={createOpen} onOpenChange={setCreateOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{duplicateFrom ? `Duplicate "${duplicateFrom.name}"` : 'New preset'}</DialogTitle>
          </DialogHeader>
          <Input
            value={createName}
            onChange={(e) => setCreateName(e.target.value)}
            placeholder="e.g. Araw ng Kalayaan"
            autoFocus
          />
          <DialogFooter>
            <Button
              disabled={!createName.trim() || createPreset.isPending}
              onClick={() =>
                createPreset.mutate(
                  { name: createName.trim(), duplicate_from: duplicateFrom?.id },
                  {
                    onSuccess: (preset) => {
                      toast.success('Preset created.')
                      setCreateOpen(false)
                      onSelect(preset.id)
                    },
                    onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Could not create.'),
                  }
                )
              }
            >
              {createPreset.isPending ? <Loader2 className='animate-spin' /> : null} Create
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={!!renameTarget} onOpenChange={(open) => !open && setRenameTarget(null)}>
        <DialogContent>
          <DialogHeader><DialogTitle>Rename preset</DialogTitle></DialogHeader>
          <Input value={renameValue} onChange={(e) => setRenameValue(e.target.value)} autoFocus />
          <DialogFooter>
            <Button
              disabled={!renameValue.trim() || renamePreset.isPending}
              onClick={() =>
                renamePreset.mutate(
                  { id: renameTarget!.id, name: renameValue.trim() },
                  {
                    onSuccess: () => { toast.success('Renamed.'); setRenameTarget(null) },
                    onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Could not rename.'),
                  }
                )
              }
            >
              Save
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <AlertDialog open={!!deleteTarget} onOpenChange={(open) => !open && setDeleteTarget(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete "{deleteTarget?.name}"?</AlertDialogTitle>
            <AlertDialogDescription>
              This cannot be undone. Any photos uploaded for this preset are removed too.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={() => {
                if (!deleteTarget) return
                const fallback = presets.find((p) => p.is_active && p.id !== deleteTarget.id) ?? presets.find((p) => p.id !== deleteTarget.id)
                deletePreset.mutate(deleteTarget.id, {
                  onSuccess: () => {
                    toast.success('Preset deleted.')
                    if (selectedId === deleteTarget.id && fallback) onSelect(fallback.id)
                    setDeleteTarget(null)
                  },
                  onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Could not delete.'),
                })
              }}
            >
              Delete
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  )
}

function ImageSectionCard({
  presetId,
  sectionKey,
  title,
  description,
  defaultColors,
  cfg,
}: {
  presetId: number
  sectionKey: string
  title: string
  description: string
  defaultColors: string[]
  cfg?: SectionConfig
}) {
  const fileRef = useRef<HTMLInputElement>(null)
  const uploadImage = useUploadSectionImage()
  const updateSettings = useUpdateSection()
  const reset = useResetSection()

  const focalX = cfg?.focal_x ?? 50
  const focalY = cfg?.focal_y ?? 50
  const fit = cfg?.fit ?? 'cover'
  const colors = cfg?.overlay_colors?.length ? cfg.overlay_colors : defaultColors
  const opacity = cfg?.overlay_opacity ?? 1

  const patch = (p: Partial<SectionConfig>) =>
    updateSettings.mutate(
      { presetId, section: sectionKey, patch: p },
      { onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Could not save.') }
    )

  const upload = (file: File) => {
    uploadImage.mutate(
      { presetId, section: sectionKey, file },
      {
        onSuccess: () => toast.success('Background updated — the app picks it up on its next refresh.'),
        onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Upload failed.'),
        onSettled: () => { if (fileRef.current) fileRef.current.value = '' },
      }
    )
  }

  const setColor = (index: number, value: string) => {
    const next = [...colors]
    next[index] = value
    patch({ overlay_colors: next })
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
          <Button size='sm' onClick={() => fileRef.current?.click()} disabled={uploadImage.isPending}>
            {uploadImage.isPending ? <Loader2 className='animate-spin' /> : <ImageUp />} Upload photo
          </Button>
          {(cfg?.image || cfg?.overlay_colors || cfg?.focal_x !== undefined) && (
            <Button
              size='sm'
              variant='outline'
              disabled={reset.isPending}
              onClick={() =>
                reset.mutate(
                  { presetId, section: sectionKey },
                  {
                    onSuccess: () => toast.success('Back to the built-in look.'),
                    onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Could not reset.'),
                  }
                )
              }
            >
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
                onClick={() => patch({ focal_x: p.x, focal_y: p.y })}
                className={`h-7 w-7 rounded border ${
                  focalX === p.x && focalY === p.y ? 'border-primary bg-primary/20' : 'border-muted-foreground/30'
                }`}
              />
            ))}
          </div>
        </div>

        <div className='flex items-center gap-2'>
          <Label className='text-xs'>Fit</Label>
          <Button size='sm' variant={fit === 'cover' ? 'default' : 'outline'} onClick={() => patch({ fit: 'cover' })}>
            Cover
          </Button>
          <Button size='sm' variant={fit === 'contain' ? 'default' : 'outline'} onClick={() => patch({ fit: 'contain' })}>
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
              onChange={(e) => patch({ overlay_opacity: Number(e.target.value) })}
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
  presetId,
  sectionKey,
  title,
  defaultBg,
  defaultText,
  cfg,
}: {
  presetId: number
  sectionKey: string
  title: string
  defaultBg: string
  defaultText: string
  cfg?: SectionConfig
}) {
  const updateSettings = useUpdateSection()
  const reset = useResetSection()
  const colors = cfg?.overlay_colors?.length === 2 ? cfg.overlay_colors : [defaultBg, defaultText]

  const setColor = (index: 0 | 1, value: string) => {
    const next = [...colors]
    next[index] = value
    updateSettings.mutate(
      { presetId, section: sectionKey, patch: { overlay_colors: next } },
      { onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Could not save.') }
    )
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
            <Button
              size='sm'
              variant='ghost'
              onClick={() =>
                reset.mutate(
                  { presetId, section: sectionKey },
                  {
                    onSuccess: () => toast.success('Back to default colors.'),
                    onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Could not reset.'),
                  }
                )
              }
            >
              <RotateCcw className='size-3.5' />
            </Button>
          )}
        </div>
      </CardContent>
    </Card>
  )
}

export function ThemePage() {
  const { data: presets, isLoading } = usePresetsQuery()
  const [selectedId, setSelectedId] = useState<number | null>(null)

  useEffect(() => {
    if (selectedId !== null || !presets?.length) return
    setSelectedId(presets.find((p) => p.is_active)?.id ?? presets[0].id)
  }, [presets, selectedId])

  const selected = presets?.find((p) => p.id === selectedId)
  const sections = selected?.sections ?? {}

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
            boxes, plus the Awards stat colors — grouped into named presets (Default, Christmas, ...). Only one
            preset is live in the app at a time.
          </p>
        </div>

        {isLoading || !presets ? (
          <p className='text-muted-foreground'>Loading presets...</p>
        ) : (
          <PresetGallery presets={presets} selectedId={selectedId} onSelect={setSelectedId} />
        )}

        {selected && (
          <>
            {!selected.is_active && (
              <div className='rounded-md border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm text-amber-900'>
                You're editing <strong>{selected.name}</strong>, which isn't live yet — hit the checkmark next to its
                name above to activate it.
              </div>
            )}

            <div className='grid gap-4 lg:grid-cols-2 xl:grid-cols-3'>
              <ImageSectionCard
                presetId={selected.id}
                sectionKey='header'
                title='Page header'
                description='Menu Plan, Community, and Prices page headers.'
                defaultColors={['#CC5027', '#EC8156']}
                cfg={sections.header}
              />
              <ImageSectionCard
                presetId={selected.id}
                sectionKey='header_hero'
                title='Profile hero header'
                description='Profile page and Awards & Achievements page headers.'
                defaultColors={['#E76739', '#E76539']}
                cfg={sections.header_hero}
              />
              <ImageSectionCard
                presetId={selected.id}
                sectionKey='header_premium'
                title='Premium subscription header'
                description='The uLam Premium (Upgrade) screen header.'
                defaultColors={['#C45E3A']}
                cfg={sections.header_premium}
              />
              <ImageSectionCard
                presetId={selected.id}
                sectionKey='dashboard_meal_plan'
                title='Home — Meal Plan card'
                description='The big hero card on Home.'
                defaultColors={['#2C341E', '#E7653B']}
                cfg={sections.dashboard_meal_plan}
              />
              <ImageSectionCard
                presetId={selected.id}
                sectionKey='dashboard_my_recipes'
                title='Home — My Recipes tile'
                description=''
                defaultColors={['#C45E3A']}
                cfg={sections.dashboard_my_recipes}
              />
              <ImageSectionCard
                presetId={selected.id}
                sectionKey='dashboard_spending_history'
                title='Home — Spending History tile'
                description=''
                defaultColors={['#E3A32A']}
                cfg={sections.dashboard_spending_history}
              />
              <ImageSectionCard
                presetId={selected.id}
                sectionKey='dashboard_awards'
                title='Home — Awards tile'
                description=''
                defaultColors={['#386641']}
                cfg={sections.dashboard_awards}
              />
              <ImageSectionCard
                presetId={selected.id}
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
                <ColorOnlyCard presetId={selected.id} sectionKey='awards_stat_saved' title='Saved' defaultBg='#F4B942' defaultText='#58200F' cfg={sections.awards_stat_saved} />
                <ColorOnlyCard presetId={selected.id} sectionKey='awards_stat_meal_plans' title='Meal Plans' defaultBg='#386641' defaultText='#FFFFFF' cfg={sections.awards_stat_meal_plans} />
                <ColorOnlyCard presetId={selected.id} sectionKey='awards_stat_posts' title='Posts' defaultBg='#E7653B' defaultText='#FFFFFF' cfg={sections.awards_stat_posts} />
                <ColorOnlyCard presetId={selected.id} sectionKey='awards_stat_achievements' title='Achievements' defaultBg='#5E693F' defaultText='#FFFFFF' cfg={sections.awards_stat_achievements} />
              </div>
            </div>
          </>
        )}
      </Main>
    </>
  )
}
