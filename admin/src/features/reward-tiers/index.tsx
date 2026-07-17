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

type RewardTier = {
  id: number
  title: string
  description: string | null
  icon: string | null
  xp_threshold: number
  is_active: boolean
}

type TierForm = {
  title: string
  description: string
  icon: string
  xp_threshold: number
  is_active: boolean
}

const EMPTY_FORM: TierForm = {
  title: '',
  description: '',
  icon: '🎁',
  xp_threshold: 200,
  is_active: true,
}

const QUERY_KEY = 'admin-reward-tiers'

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

  const save = useMutation({
    mutationFn: async () => {
      const body = { ...form, description: form.description || null }
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
            description: tier.description ?? '',
            icon: tier.icon ?? '🎁',
            xp_threshold: tier.xp_threshold,
            is_active: tier.is_active,
          }
    )
    setEditing(tier)
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
              XP milestones a user can reach (e.g. "200 XP unlocks 3 free
              AI-generated meal plans"). Scaffolding only for now — describe
              the reward in the title/description; nothing is granted or
              redeemable automatically yet, and tiers aren't shown on mobile
              until that's built.
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
              .sort((a, b) => a.xp_threshold - b.xp_threshold)
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
                        <Badge variant='secondary'>
                          {tier.xp_threshold} XP
                        </Badge>
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
                  placeholder='3 Free AI Meal Plans'
                  value={form.title}
                  onChange={(e) =>
                    setForm((f) => ({ ...f, title: e.target.value }))
                  }
                />
              </div>
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
              <Label>XP threshold</Label>
              <Input
                type='number'
                min={0}
                value={form.xp_threshold}
                onChange={(e) =>
                  setForm((f) => ({
                    ...f,
                    xp_threshold: Number(e.target.value),
                  }))
                }
              />
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
              disabled={!form.title.trim() || save.isPending}
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
