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

type Frequency = 'daily' | 'weekly' | 'monthly' | 'once'
type Tier = 'bronze' | 'silver' | 'gold' | 'diamond'

type Task = {
  id: number
  slug: string
  title: string
  title_en: string | null
  description: string
  description_en: string | null
  icon: string | null
  xp_reward: number
  action_type: string | null
  frequency: Frequency
  target_count: number
  tier: Tier | null
  tier_group: string | null
  is_active: boolean
}

type ActionType = { id: number; key: string; label: string; is_active: boolean }

type TaskForm = {
  title: string
  title_en: string
  description: string
  description_en: string
  icon: string
  xp_reward: number
  action_type: string // '__none' represents an intentionally-inert task
  frequency: Frequency
  target_count: number
  tier: string // '' | Tier -- Radix Select can't take an empty-string item value
  tier_group: string
  is_active: boolean
}

const NONE_ACTION = '__none'
const NO_TIER = '__no_tier'

const EMPTY_FORM: TaskForm = {
  title: '',
  title_en: '',
  description: '',
  description_en: '',
  icon: '🎯',
  xp_reward: 10,
  action_type: NONE_ACTION,
  frequency: 'daily',
  target_count: 1,
  tier: NO_TIER,
  tier_group: '',
  is_active: true,
}

const TIER_LABEL: Record<Tier, string> = {
  bronze: 'Bronze',
  silver: 'Silver',
  gold: 'Gold',
  diamond: 'Diamond',
}

const TIER_COLOR: Record<Tier, string> = {
  bronze: 'bg-orange-100 text-orange-800',
  silver: 'bg-slate-200 text-slate-800',
  gold: 'bg-amber-100 text-amber-800',
  diamond: 'bg-cyan-100 text-cyan-800',
}

const QUERY_KEY = 'admin-tasks'
const ACTION_TYPES_QUERY_KEY = 'admin-task-action-types'

export function Tasks() {
  const qc = useQueryClient()
  const [editing, setEditing] = useState<Task | 'new' | null>(null)
  const [deleting, setDeleting] = useState<Task | null>(null)
  const [form, setForm] = useState<TaskForm>(EMPTY_FORM)

  const { data, isLoading } = useQuery({
    queryKey: [QUERY_KEY],
    queryFn: async () => {
      const { data } = await apiClient.get<{ tasks: Task[] }>('/admin/tasks')
      return data.tasks
    },
  })

  const { data: actionTypes } = useQuery({
    queryKey: [ACTION_TYPES_QUERY_KEY],
    queryFn: async () => {
      const { data } = await apiClient.get<{ action_types: ActionType[] }>(
        '/admin/task-action-types'
      )
      return data.action_types
    },
  })

  const save = useMutation({
    mutationFn: async () => {
      const isOnce = form.frequency === 'once'
      const body = {
        ...form,
        title_en: form.title_en || null,
        description_en: form.description_en || null,
        action_type: form.action_type === NONE_ACTION ? null : form.action_type,
        tier: isOnce && form.tier !== NO_TIER ? form.tier : null,
        tier_group: isOnce && form.tier_group.trim() ? form.tier_group.trim() : null,
      }
      if (editing === 'new') {
        return apiClient.post('/admin/tasks', body)
      }
      return apiClient.patch(`/admin/tasks/${(editing as Task).id}`, body)
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: [QUERY_KEY] })
      toast.success('Task saved.')
      setEditing(null)
    },
    onError: (error: any) =>
      toast.error(error?.response?.data?.message ?? 'Could not save task.'),
  })

  const remove = useMutation({
    mutationFn: async (id: number) => apiClient.delete(`/admin/tasks/${id}`),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: [QUERY_KEY] })
      toast.success('Task deleted.')
      setDeleting(null)
    },
  })

  const openEditor = (task: Task | 'new') => {
    setForm(
      task === 'new'
        ? EMPTY_FORM
        : {
            title: task.title,
            title_en: task.title_en ?? '',
            description: task.description,
            description_en: task.description_en ?? '',
            icon: task.icon ?? '🎯',
            xp_reward: task.xp_reward,
            action_type: task.action_type ?? NONE_ACTION,
            frequency: task.frequency,
            target_count: task.target_count,
            tier: task.tier ?? NO_TIER,
            tier_group: task.tier_group ?? '',
            is_active: task.is_active,
          }
    )
    setEditing(task)
  }

  const isOnce = form.frequency === 'once'

  // Cluster rows sharing a tier_group together, in ascending tier order;
  // everything else (repeating tasks, inert once-tasks) renders as its own
  // flat row like before.
  const tierOrder: Record<Tier, number> = { bronze: 0, silver: 1, gold: 2, diamond: 3 }
  const grouped: { key: string; tasks: Task[] }[] = []
  const seen = new Set<number>()
  ;(data ?? []).forEach((task) => {
    if (seen.has(task.id)) return
    if (task.tier_group) {
      const groupTasks = (data ?? [])
        .filter((t) => t.tier_group === task.tier_group)
        .sort((a, b) => tierOrder[a.tier as Tier] - tierOrder[b.tier as Tier])
      groupTasks.forEach((t) => seen.add(t.id))
      grouped.push({ key: `group-${task.tier_group}`, tasks: groupTasks })
    } else {
      seen.add(task.id)
      grouped.push({ key: `task-${task.id}`, tasks: [task] })
    }
  })

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
            <h2 className='text-2xl font-bold tracking-tight'>Tasks</h2>
            <p className='text-muted-foreground'>
              Shown on the app's Awards screen as Daily/Weekly/Monthly
              checklists and lifetime Achievements. A task auto-completes
              (and awards its bonus XP) the moment a user performs the
              matching action — there's no manual "mark done" on the app
              side. Lifetime tasks sharing a "Tier group" render as one
              progressive badge (bronze → diamond) in the app.
            </p>
          </div>
          <Button onClick={() => openEditor('new')}>
            <Plus className='me-1 size-4' /> Add Task
          </Button>
        </div>

        <div className='flex flex-col gap-3'>
          {isLoading ? (
            <p className='py-12 text-center text-muted-foreground'>
              Loading...
            </p>
          ) : grouped.length === 0 ? (
            <p className='py-12 text-center text-muted-foreground'>
              No tasks yet.
            </p>
          ) : (
            grouped.map((cluster) => (
              <div
                key={cluster.key}
                className={
                  cluster.tasks.length > 1
                    ? 'flex flex-col gap-2 rounded-md border-2 border-dashed p-2'
                    : 'flex flex-col gap-2'
                }
              >
                {cluster.tasks.length > 1 && (
                  <p className='px-1 text-xs font-medium text-muted-foreground'>
                    Tier group: {cluster.tasks[0].tier_group}
                  </p>
                )}
                {cluster.tasks.map((task) => (
                  <div
                    key={task.id}
                    className='flex items-start justify-between gap-3 rounded-md border p-3'
                  >
                    <div className='flex min-w-0 items-start gap-3'>
                      <span className='text-xl'>{task.icon || '🎯'}</span>
                      <div className='min-w-0'>
                        <div className='flex flex-wrap items-center gap-2'>
                          <span className='font-medium'>{task.title}</span>
                          <Badge variant='outline' className='capitalize'>
                            {task.frequency}
                          </Badge>
                          {task.tier && (
                            <Badge className={TIER_COLOR[task.tier]}>
                              {TIER_LABEL[task.tier]}
                            </Badge>
                          )}
                          <Badge variant='secondary'>+{task.xp_reward} XP</Badge>
                          {task.target_count > 1 && (
                            <Badge variant='outline'>×{task.target_count}</Badge>
                          )}
                          {!task.is_active && (
                            <Badge className='bg-muted text-muted-foreground'>
                              Inactive
                            </Badge>
                          )}
                        </div>
                        {task.description && (
                          <p className='mt-0.5 line-clamp-2 text-sm text-muted-foreground'>
                            {task.description}
                          </p>
                        )}
                        <p className='mt-0.5 font-mono text-xs text-muted-foreground'>
                          action_type: {task.action_type ?? 'none (inert)'}
                        </p>
                      </div>
                    </div>
                    <div className='flex shrink-0 gap-1'>
                      <Button
                        size='icon'
                        variant='ghost'
                        className='size-8'
                        onClick={() => openEditor(task)}
                      >
                        <Pencil className='size-4' />
                      </Button>
                      <Button
                        size='icon'
                        variant='ghost'
                        className='size-8 text-destructive'
                        onClick={() => setDeleting(task)}
                      >
                        <Trash2 className='size-4' />
                      </Button>
                    </div>
                  </div>
                ))}
              </div>
            ))
          )}
        </div>
      </Main>

      <Dialog open={!!editing} onOpenChange={(o) => !o && setEditing(null)}>
        <DialogContent className='max-h-[90vh] overflow-y-auto sm:max-w-lg'>
          <DialogHeader>
            <DialogTitle>
              {editing === 'new' ? 'Add Task' : 'Edit Task'}
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
                <Label>Frequency</Label>
                <Select
                  value={form.frequency}
                  onValueChange={(v) =>
                    setForm((f) => ({ ...f, frequency: v as Frequency }))
                  }
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value='daily'>Daily</SelectItem>
                    <SelectItem value='weekly'>Weekly</SelectItem>
                    <SelectItem value='monthly'>Monthly</SelectItem>
                    <SelectItem value='once'>Once (lifetime)</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className='space-y-1.5'>
                <Label>Bonus XP</Label>
                <Input
                  type='number'
                  min={0}
                  value={form.xp_reward}
                  onChange={(e) =>
                    setForm((f) => ({ ...f, xp_reward: Number(e.target.value) }))
                  }
                />
              </div>
            </div>
            <div className='space-y-1.5'>
              <Label>Target count</Label>
              <Input
                type='number'
                min={1}
                value={form.target_count}
                onChange={(e) =>
                  setForm((f) => ({ ...f, target_count: Number(e.target.value) }))
                }
              />
              <p className='text-xs text-muted-foreground'>
                {isOnce
                  ? 'How many times this action must happen (ever) before this lifetime task completes.'
                  : 'How many times this action must happen within the period (daily/weekly/monthly). Usually 1.'}
              </p>
            </div>
            {isOnce && (
              <div className='grid grid-cols-2 gap-3 rounded-md border p-3'>
                <div className='space-y-1.5'>
                  <Label>Tier</Label>
                  <Select
                    value={form.tier}
                    onValueChange={(v) => setForm((f) => ({ ...f, tier: v }))}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value={NO_TIER}>Not tiered</SelectItem>
                      <SelectItem value='bronze'>Bronze</SelectItem>
                      <SelectItem value='silver'>Silver</SelectItem>
                      <SelectItem value='gold'>Gold</SelectItem>
                      <SelectItem value='diamond'>Diamond</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className='space-y-1.5'>
                  <Label>Tier group</Label>
                  <Input
                    placeholder='e.g. recipe_collector'
                    value={form.tier_group}
                    onChange={(e) =>
                      setForm((f) => ({ ...f, tier_group: e.target.value }))
                    }
                    disabled={form.tier === NO_TIER}
                  />
                  <p className='text-xs text-muted-foreground'>
                    Same value across a tier's 4 rows groups them into one
                    badge in the app.
                  </p>
                </div>
              </div>
            )}
            <div className='space-y-1.5'>
              <Label>Completes when the user...</Label>
              <Select
                value={form.action_type}
                onValueChange={(v) => setForm((f) => ({ ...f, action_type: v }))}
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value={NONE_ACTION}>
                    — None (inert, no auto-complete yet) —
                  </SelectItem>
                  {(actionTypes ?? [])
                    .filter((a) => a.is_active)
                    .map((a) => (
                      <SelectItem key={a.key} value={a.key}>
                        {a.label}
                      </SelectItem>
                    ))}
                </SelectContent>
              </Select>
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
        title='Delete this task?'
        destructive
        desc={deleting?.title ?? ''}
        confirmText='Delete'
        isLoading={remove.isPending}
        handleConfirm={() => deleting && remove.mutate(deleting.id)}
      />
    </>
  )
}
