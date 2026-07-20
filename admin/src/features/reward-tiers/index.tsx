import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import { Pencil, Plus, Trash2 } from 'lucide-react'
import apiClient from '@/lib/api-client'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { EmojiPicker } from '@/components/emoji-picker'
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

type RewardType = 'premium_days' | 'booster_credit' | 'store_boost_credit' | 'badge'

type TaskRef = { id: number; title: string; icon: string | null }

type RewardTier = {
  id: number
  title: string
  title_en: string | null
  description: string | null
  description_en: string | null
  icon: string | null
  xp_threshold: number | null
  reward_type: RewardType
  reward_value: number | null
  required_tasks: TaskRef[]
  is_active: boolean
}

type AdminTask = { id: number; title: string; icon: string | null; is_active: boolean }

type TierForm = {
  title: string
  title_en: string
  description: string
  description_en: string
  icon: string
  xp_threshold: string // '' means unset -- kept as a string so an empty input doesn't coerce to 0
  reward_type: RewardType
  reward_value: number
  required_task_ids: number[]
  is_active: boolean
}

const EMPTY_FORM: TierForm = {
  title: '',
  title_en: '',
  description: '',
  description_en: '',
  icon: '🎁',
  xp_threshold: '',
  reward_type: 'premium_days',
  reward_value: 3,
  required_task_ids: [],
  is_active: true,
}

const REWARD_TYPE_LABEL: Record<RewardType, string> = {
  premium_days: 'Premium (days)',
  booster_credit: 'Boost credit — recipe',
  store_boost_credit: 'Boost credit — store',
  badge: 'Badge (cosmetic)',
}

const REWARD_TYPE_BADGE: Record<RewardType, string> = {
  premium_days: 'bg-amber-100 text-amber-800',
  booster_credit: 'bg-sky-100 text-sky-800',
  store_boost_credit: 'bg-indigo-100 text-indigo-800',
  badge: 'bg-emerald-100 text-emerald-800',
}

function rewardSummary(tier: RewardTier): string {
  switch (tier.reward_type) {
    case 'premium_days':
      return `🎁 ${tier.reward_value ?? '?'} days Premium`
    case 'booster_credit':
      return `🚀 ${tier.reward_value ?? '?'}d boost credit (recipe)`
    case 'store_boost_credit':
      return `🚀 ${tier.reward_value ?? '?'}d boost credit (store)`
    case 'badge':
      return `🏅 Badge`
  }
}

const QUERY_KEY = 'admin-reward-tiers'
const TASKS_QUERY_KEY = 'admin-tasks'

export function RewardTiers() {
  const qc = useQueryClient()
  const [editing, setEditing] = useState<RewardTier | 'new' | null>(null)
  const [deleting, setDeleting] = useState<RewardTier | null>(null)
  const [form, setForm] = useState<TierForm>(EMPTY_FORM)

  const { data, isLoading } = useQuery({
    queryKey: [QUERY_KEY],
    queryFn: async () => {
      const { data } = await apiClient.get<{ tiers: RewardTier[] }>(
        '/admin/reward-tiers'
      )
      return data.tiers
    },
  })

  const { data: tasks } = useQuery({
    queryKey: [TASKS_QUERY_KEY],
    queryFn: async () => {
      const { data } = await apiClient.get<{ tasks: AdminTask[] }>('/admin/tasks')
      return data.tasks
    },
  })

  const save = useMutation({
    mutationFn: async () => {
      const body = {
        title: form.title,
        title_en: form.title_en || null,
        description: form.description || null,
        description_en: form.description_en || null,
        icon: form.icon,
        xp_threshold: form.xp_threshold === '' ? null : Number(form.xp_threshold),
        reward_type: form.reward_type,
        reward_value: form.reward_type === 'badge' ? null : form.reward_value,
        required_task_ids: form.required_task_ids,
        is_active: form.is_active,
      }
      if (editing === 'new') {
        return apiClient.post('/admin/reward-tiers', body)
      }
      return apiClient.patch(
        `/admin/reward-tiers/${(editing as RewardTier).id}`,
        body
      )
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: [QUERY_KEY] })
      toast.success('Reward tier saved.')
      setEditing(null)
    },
    onError: (error: any) =>
      toast.error(
        error?.response?.data?.message ?? 'Could not save reward tier.'
      ),
  })

  const remove = useMutation({
    mutationFn: async (id: number) =>
      apiClient.delete(`/admin/reward-tiers/${id}`),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: [QUERY_KEY] })
      toast.success('Reward tier deleted.')
      setDeleting(null)
    },
  })

  const openEditor = (tier: RewardTier | 'new') => {
    setForm(
      tier === 'new'
        ? EMPTY_FORM
        : {
            title: tier.title,
            title_en: tier.title_en ?? '',
            description: tier.description ?? '',
            description_en: tier.description_en ?? '',
            icon: tier.icon ?? '🎁',
            xp_threshold: tier.xp_threshold === null ? '' : String(tier.xp_threshold),
            reward_type: tier.reward_type,
            reward_value: tier.reward_value ?? 3,
            required_task_ids: tier.required_tasks.map((t) => t.id),
            is_active: tier.is_active,
          }
    )
    setEditing(tier)
  }

  const toggleTask = (id: number) => {
    setForm((f) => ({
      ...f,
      required_task_ids: f.required_task_ids.includes(id)
        ? f.required_task_ids.filter((t) => t !== id)
        : [...f.required_task_ids, id],
    }))
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
            <h2 className='text-2xl font-bold tracking-tight'>
              Reward Tiers
            </h2>
            <p className='text-muted-foreground'>
              Unlocks automatically when a user completes every required task
              below (and reaches the XP threshold, if set). Premium days and
              badges are granted immediately; boost credits are banked for
              the user to spend from the Awards screen.
            </p>
          </div>
          <Button onClick={() => openEditor('new')}>
            <Plus className='me-1 size-4' /> Add Tier
          </Button>
        </div>

        <div className='flex flex-col gap-2'>
          {isLoading ? (
            <p className='py-12 text-center text-muted-foreground'>
              Loading...
            </p>
          ) : (data ?? []).length === 0 ? (
            <p className='py-12 text-center text-muted-foreground'>
              No reward tiers yet.
            </p>
          ) : (
            [...(data ?? [])]
              .sort((a, b) => (a.xp_threshold ?? Infinity) - (b.xp_threshold ?? Infinity))
              .map((tier) => (
                <div
                  key={tier.id}
                  className='flex items-start justify-between gap-3 rounded-md border p-3'
                >
                  <div className='flex min-w-0 items-start gap-3'>
                    <span className='text-xl'>{tier.icon || '🎁'}</span>
                    <div className='min-w-0'>
                      <div className='flex flex-wrap items-center gap-2'>
                        <span className='font-medium'>{tier.title}</span>
                        <Badge className={REWARD_TYPE_BADGE[tier.reward_type]}>
                          {rewardSummary(tier)}
                        </Badge>
                        {tier.xp_threshold !== null && (
                          <Badge variant='secondary'>{tier.xp_threshold} XP</Badge>
                        )}
                        {!tier.is_active && (
                          <Badge className='bg-muted text-muted-foreground'>
                            Inactive
                          </Badge>
                        )}
                      </div>
                      {tier.description && (
                        <p className='mt-0.5 line-clamp-2 text-sm text-muted-foreground'>
                          {tier.description}
                        </p>
                      )}
                      {tier.required_tasks.length > 0 && (
                        <div className='mt-1 flex flex-wrap gap-1'>
                          {tier.required_tasks.map((t) => (
                            <Badge key={t.id} variant='outline' className='text-xs'>
                              {t.icon} {t.title}
                            </Badge>
                          ))}
                        </div>
                      )}
                    </div>
                  </div>
                  <div className='flex shrink-0 gap-1'>
                    <Button
                      size='icon'
                      variant='ghost'
                      className='size-8'
                      onClick={() => openEditor(tier)}
                    >
                      <Pencil className='size-4' />
                    </Button>
                    <Button
                      size='icon'
                      variant='ghost'
                      className='size-8 text-destructive'
                      onClick={() => setDeleting(tier)}
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
            <DialogTitle>
              {editing === 'new' ? 'Add Reward Tier' : 'Edit Reward Tier'}
            </DialogTitle>
          </DialogHeader>
          <div className='space-y-3'>
            <div className='flex gap-3'>
              <div className='space-y-1.5'>
                <Label>Icon</Label>
                <EmojiPicker
                  value={form.icon}
                  onChange={(icon) => setForm((f) => ({ ...f, icon }))}
                />
              </div>
              <div className='flex-1 space-y-1.5'>
                <Label>Title</Label>
                <Input
                  placeholder='3 Araw na Libreng Premium'
                  value={form.title}
                  onChange={(e) =>
                    setForm((f) => ({ ...f, title: e.target.value }))
                  }
                />
              </div>
            </div>
            <div className='space-y-1.5'>
              <Label>Title (English)</Label>
              <Input
                placeholder='Falls back to the Title above if left blank'
                value={form.title_en}
                onChange={(e) =>
                  setForm((f) => ({ ...f, title_en: e.target.value }))
                }
              />
            </div>
            <div className='space-y-1.5'>
              <Label>Description</Label>
              <Textarea
                rows={2}
                value={form.description}
                onChange={(e) =>
                  setForm((f) => ({ ...f, description: e.target.value }))
                }
              />
            </div>
            <div className='space-y-1.5'>
              <Label>Description (English)</Label>
              <Textarea
                rows={2}
                value={form.description_en}
                onChange={(e) =>
                  setForm((f) => ({ ...f, description_en: e.target.value }))
                }
              />
            </div>
            <div className='grid grid-cols-2 gap-3'>
              <div className='space-y-1.5'>
                <Label>Reward type</Label>
                <Select
                  value={form.reward_type}
                  onValueChange={(v) =>
                    setForm((f) => ({ ...f, reward_type: v as RewardType }))
                  }
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {(Object.keys(REWARD_TYPE_LABEL) as RewardType[]).map((rt) => (
                      <SelectItem key={rt} value={rt}>
                        {REWARD_TYPE_LABEL[rt]}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              {form.reward_type !== 'badge' && (
                <div className='space-y-1.5'>
                  <Label>
                    {form.reward_type === 'premium_days'
                      ? 'Days'
                      : form.reward_type === 'booster_credit'
                        ? 'Boost duration (days) — recipe'
                        : 'Boost duration (days) — store'}
                  </Label>
                  <Input
                    type='number'
                    min={1}
                    value={form.reward_value}
                    onChange={(e) =>
                      setForm((f) => ({ ...f, reward_value: Number(e.target.value) }))
                    }
                  />
                </div>
              )}
            </div>
            <div className='space-y-1.5'>
              <Label>XP threshold (optional)</Label>
              <Input
                type='number'
                min={0}
                placeholder='Leave blank to gate purely on tasks below'
                value={form.xp_threshold}
                onChange={(e) =>
                  setForm((f) => ({ ...f, xp_threshold: e.target.value }))
                }
              />
            </div>
            <div className='space-y-1.5'>
              <Label>Required tasks</Label>
              <p className='text-xs text-muted-foreground'>
                A user must complete every task checked below to earn this tier.
              </p>
              <div className='max-h-48 space-y-1 overflow-y-auto rounded-md border p-2'>
                {(tasks ?? []).filter((t) => t.is_active).length === 0 ? (
                  <p className='p-2 text-sm text-muted-foreground'>No active tasks yet.</p>
                ) : (
                  (tasks ?? [])
                    .filter((t) => t.is_active)
                    .map((t) => (
                      <label
                        key={t.id}
                        className='flex cursor-pointer items-center gap-2 rounded px-2 py-1 hover:bg-muted'
                      >
                        <input
                          type='checkbox'
                          checked={form.required_task_ids.includes(t.id)}
                          onChange={() => toggleTask(t.id)}
                        />
                        <span>{t.icon}</span>
                        <span className='text-sm'>{t.title}</span>
                      </label>
                    ))
                )}
              </div>
            </div>
            <div className='flex items-center justify-between rounded-md border p-3'>
              <Label>Active</Label>
              <Switch
                checked={form.is_active}
                onCheckedChange={(v) =>
                  setForm((f) => ({ ...f, is_active: v }))
                }
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant='outline' onClick={() => setEditing(null)}>
              Cancel
            </Button>
            <Button
              disabled={
                !form.title.trim() ||
                (form.xp_threshold === '' && form.required_task_ids.length === 0) ||
                save.isPending
              }
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
        title='Delete this reward tier?'
        destructive
        desc={deleting?.title ?? ''}
        confirmText='Delete'
        isLoading={remove.isPending}
        handleConfirm={() => deleting && remove.mutate(deleting.id)}
      />
    </>
  )
}
