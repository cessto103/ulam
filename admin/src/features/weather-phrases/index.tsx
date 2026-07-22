import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import { Pencil, Plus, Trash2 } from 'lucide-react'
import apiClient from '@/lib/api-client'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Switch } from '@/components/ui/switch'
import { Textarea } from '@/components/ui/textarea'
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'

type WeatherCategory = 'sunny' | 'cloudy' | 'light_rain' | 'heavy_rain' | 'extended_rain'
type VariantType = 'info' | 'meal_promo' | 'premium_promo'

type WeatherPhrase = {
  id: number
  weather_category: WeatherCategory
  variant_type: VariantType
  phrase_text: string
  is_active: boolean
  sort: number
}

type WeatherPhraseForm = {
  weather_category: WeatherCategory
  variant_type: VariantType
  phrase_text: string
  is_active: boolean
  sort: number
}

const CATEGORY_LABEL: Record<WeatherCategory, string> = {
  sunny: 'Sunny',
  cloudy: 'Cloudy',
  light_rain: 'Light rain',
  heavy_rain: 'Heavy rain',
  extended_rain: 'Extended rain (3+ days)',
}

const VARIANT_LABEL: Record<VariantType, string> = {
  info: 'Info only',
  meal_promo: 'Meal promo',
  premium_promo: 'Premium promo',
}

const EMPTY_FORM: WeatherPhraseForm = {
  weather_category: 'sunny',
  variant_type: 'info',
  phrase_text: '',
  is_active: true,
  sort: 0,
}

const QUERY_KEY = 'admin-weather-phrases'

export function WeatherPhrases() {
  const qc = useQueryClient()
  const [editing, setEditing] = useState<WeatherPhrase | 'new' | null>(null)
  const [deleting, setDeleting] = useState<WeatherPhrase | null>(null)
  const [form, setForm] = useState<WeatherPhraseForm>(EMPTY_FORM)

  const { data, isLoading } = useQuery({
    queryKey: [QUERY_KEY],
    queryFn: async () => {
      const { data } = await apiClient.get<{ weather_phrases: WeatherPhrase[] }>(
        '/admin/weather-phrases'
      )
      return data.weather_phrases
    },
  })

  const save = useMutation({
    mutationFn: async () => {
      if (editing === 'new') {
        return apiClient.post('/admin/weather-phrases', form)
      }
      return apiClient.patch(`/admin/weather-phrases/${(editing as WeatherPhrase).id}`, form)
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: [QUERY_KEY] })
      toast.success('Weather phrase saved.')
      setEditing(null)
    },
    onError: (error: any) =>
      toast.error(error?.response?.data?.message ?? 'Could not save weather phrase.'),
  })

  const remove = useMutation({
    mutationFn: async (id: number) => apiClient.delete(`/admin/weather-phrases/${id}`),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: [QUERY_KEY] })
      toast.success('Weather phrase deleted.')
      setDeleting(null)
    },
  })

  const openEditor = (phrase: WeatherPhrase | 'new') => {
    setForm(
      phrase === 'new'
        ? EMPTY_FORM
        : {
            weather_category: phrase.weather_category,
            variant_type: phrase.variant_type,
            phrase_text: phrase.phrase_text,
            is_active: phrase.is_active,
            sort: phrase.sort,
          }
    )
    setEditing(phrase)
  }

  return (
    <>
      <Header fixed>
        <Search className='me-auto' />
        <ThemeSwitch />
        <ConfigDrawer />
        <ProfileDropdown />
      </Header>

      <Main className='flex flex-1 flex-col gap-4 sm:gap-6'>
        <div className='flex flex-wrap items-end justify-between gap-2'>
          <div>
            <h2 className='text-2xl font-bold tracking-tight'>Weather Phrases</h2>
            <p className='text-muted-foreground'>
              Messages shown in the app's daily weather notification. Add a
              few per category so it doesn't repeat the same wording every
              day — the system rotates between active phrases and fills in{' '}
              <code>{'{{recipe_name}}'}</code>, <code>{'{{recipe_author}}'}</code>,{' '}
              <code>{'{{rating}}'}</code>, <code>{'{{thumbs_count}}'}</code>, and{' '}
              <code>{'{{days}}'}</code> automatically.
            </p>
          </div>
          <Button onClick={() => openEditor('new')}>
            <Plus className='me-1 size-4' /> Add Phrase
          </Button>
        </div>

        <div className='flex flex-col gap-2'>
          {isLoading ? (
            <p className='py-12 text-center text-muted-foreground'>Loading...</p>
          ) : (data ?? []).length === 0 ? (
            <p className='py-12 text-center text-muted-foreground'>
              No weather phrases yet.
            </p>
          ) : (
            (data ?? []).map((phrase) => (
              <div
                key={phrase.id}
                className='flex items-start justify-between gap-3 rounded-md border p-3'
              >
                <div className='min-w-0'>
                  <div className='flex flex-wrap items-center gap-2'>
                    <Badge variant='outline'>{CATEGORY_LABEL[phrase.weather_category]}</Badge>
                    <Badge variant='secondary'>{VARIANT_LABEL[phrase.variant_type]}</Badge>
                    {!phrase.is_active && (
                      <Badge className='bg-muted text-muted-foreground'>Inactive</Badge>
                    )}
                  </div>
                  <p className='mt-0.5 line-clamp-2 text-sm text-muted-foreground'>
                    {phrase.phrase_text}
                  </p>
                </div>
                <div className='flex shrink-0 gap-1'>
                  <Button
                    size='icon'
                    variant='ghost'
                    className='size-8'
                    onClick={() => openEditor(phrase)}
                  >
                    <Pencil className='size-4' />
                  </Button>
                  <Button
                    size='icon'
                    variant='ghost'
                    className='size-8 text-destructive'
                    onClick={() => setDeleting(phrase)}
                  >
                    <Trash2 className='size-4' />
                  </Button>
                </div>
              </div>
            ))
          )}
        </div>
      </Main>

      <Dialog open={!!editing} onOpenChange={(o) => !o && setEditing(null)}>
        <DialogContent className='max-h-[90vh] overflow-y-auto sm:max-w-lg'>
          <DialogHeader>
            <DialogTitle>{editing === 'new' ? 'Add Weather Phrase' : 'Edit Weather Phrase'}</DialogTitle>
          </DialogHeader>
          <div className='space-y-3'>
            <div className='grid grid-cols-2 gap-3'>
              <div className='space-y-1.5'>
                <Label>Weather</Label>
                <Select
                  value={form.weather_category}
                  onValueChange={(v: WeatherCategory) =>
                    setForm((f) => ({ ...f, weather_category: v }))
                  }
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {Object.entries(CATEGORY_LABEL).map(([value, label]) => (
                      <SelectItem key={value} value={value}>
                        {label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className='space-y-1.5'>
                <Label>Variant</Label>
                <Select
                  value={form.variant_type}
                  onValueChange={(v: VariantType) => setForm((f) => ({ ...f, variant_type: v }))}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {Object.entries(VARIANT_LABEL).map(([value, label]) => (
                      <SelectItem key={value} value={value}>
                        {label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>
            <div className='space-y-1.5'>
              <Label>Phrase</Label>
              <Textarea
                rows={4}
                placeholder="e.g. It's a good day to go to the market today, we'll be having a sunny day!"
                value={form.phrase_text}
                onChange={(e) => setForm((f) => ({ ...f, phrase_text: e.target.value }))}
              />
              <p className='text-xs text-muted-foreground'>
                Tokens: <code>{'{{recipe_name}}'}</code> <code>{'{{recipe_author}}'}</code>{' '}
                <code>{'{{rating}}'}</code> <code>{'{{thumbs_count}}'}</code> <code>{'{{days}}'}</code>{' '}
                — only fill in recipe tokens for a Meal promo phrase, and{' '}
                <code>{'{{days}}'}</code> for Extended rain phrases.
              </p>
            </div>
            <div className='space-y-1.5'>
              <Label>Sort order</Label>
              <Input
                type='number'
                min={0}
                value={form.sort}
                onChange={(e) => setForm((f) => ({ ...f, sort: Number(e.target.value) }))}
              />
            </div>
            <div className='flex items-center justify-between rounded-md border p-3'>
              <Label>Active</Label>
              <Switch
                checked={form.is_active}
                onCheckedChange={(v) => setForm((f) => ({ ...f, is_active: v }))}
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant='outline' onClick={() => setEditing(null)}>
              Cancel
            </Button>
            <Button
              disabled={!form.phrase_text.trim() || save.isPending}
              onClick={() => save.mutate()}
            >
              Save
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <ConfirmDialog
        open={!!deleting}
        onOpenChange={(o) => !o && setDeleting(null)}
        title='Delete this weather phrase?'
        destructive
        desc={deleting?.phrase_text ?? ''}
        confirmText='Delete'
        isLoading={remove.isPending}
        handleConfirm={() => deleting && remove.mutate(deleting.id)}
      />
    </>
  )
}
