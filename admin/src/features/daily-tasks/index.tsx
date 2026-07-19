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

type DailyTask = {
  id: number
  slug: string
  title: string
  description: string | null
  icon: string | null
  xp_reward: number
  action_type: string
  frequency: 'daily' | 'weekly'
  is_active: boolean
}

type TaskForm = {
  title: string
  description: string
  icon: string
  xp_reward: number
  action_type: string
  frequency: 'daily' | 'weekly'
  is_active: boolean
}

const EMPTY_FORM: TaskForm = {
  title: '',
  description: '',
  icon: '🎯',
  xp_reward: 10,
  action_type: 'generate_meal_plan',
  frequency: 'daily',
  is_active: true,
}

// Matches the reasons XpService::award() actually fires — a task's
// action_type must match one of these (or a real future one) to ever
// auto-complete. Picking from this list instead of free-typing avoids the
// exact action_type/reason mismatch bug that shipped with the original
// seed data (log_spending vs. the real log_budget).
const KNOWN_ACTION_TYPES = [
  { value: 'generate_meal_plan', label: 'Generate a meal plan' },
  { value: 'report_price', label: 'Report a price' },
  { value: 'create_post', label: 'Create a community post' },
  { value: 'log_budget', label: 'Log daily spending' },
  { value: 'help_shopping', label: 'Help with a shared shopping list' },
]

const QUERY_KEY = 'admin-daily-tasks'

export function DailyTasks() {
  const qc = useQueryClient()
  const [editing, setEditing] = useState<DailyTask | 'new' | null>(null)
  const [deleting, setDeleting] = useState<DailyTask | null>(null)
  const [form, setForm] = useState<TaskForm>(EMPTY_FORM)

  const { data, isLoading } = useQuery({
    queryKey: [QUERY_KEY],
    queryFn: async () => {
      const { data } = await apiClient.get<{ tasks: DailyTask[] }>(
        '/admin/daily-tasks'
      )
      return data.tasks
    },
  })

  const save = useMutation({
    mutationFn: async () => {
      const body = { ...form, description: form.description || null }
      if (editing === 'new') {
        return apiClient.post('/admin/daily-tasks', body)
      }
      return apiClient.patch(
        `/admin/daily-tasks/${(editing as DailyTask).id}`,
        body
      )
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
    mutationFn: async (id: number) => apiClient.delete(`/admin/daily-tasks/${id}`),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: [QUERY_KEY] })
      toast.success('Task deleted.')
      setDeleting(null)
    },
  })

  const openEditor = (task: DailyTask | 'new') => {
    setForm(
      task === 'new'
        ? EMPTY_FORM
        : {
            title: task.title,
            description: task.description ?? '',
            icon: task.icon ?? '🎯',
            xp_reward: task.xp_reward,
            action_type: task.action_type,
            frequency: task.frequency,
            is_active: task.is_active,
          }
    )
    setEditing(task)
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
              Daily &amp; Weekly Tasks
            </h2>
            <p className='text-muted-foreground'>
              Shown as a checklist on the app's Awards screen. A task
              auto-completes (and awards its bonus XP) the moment a user
              performs the matching action — there's no manual "mark done" on
              the app side.
            </p>
          </div>
          <Button onClick={() => openEditor('new')}>
            <Plus className='me-1 size-4' /> Add Task
          </Button>
        </div>

        <div className='flex flex-col gap-2'>
          {isLoading ? (
            <p className='py-12 text-center text-muted-foreground'>
              Loading...
            </p>
          ) : (data ?? []).length === 0 ? (
            <p className='py-12 text-center text-muted-foreground'>
              No tasks yet.
            </p>
          ) : (
            (data ?? []).map((task) => (
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
                      <Badge variant='secondary'>+{task.xp_reward} XP</Badge>
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
                      action_type: {task.action_type}
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
              <Label>Description</Label>
              <Textarea
                rows={2}
                value={form.description}
                onChange={(e) =>
                  setForm((f) => ({ ...f, description: e.target.value }))
                }
              />
            </div>
            <div className='grid grid-cols-2 gap-3'>
              <div className='space-y-1.5'>
                <Label>Frequency</Label>
                <Select
                  value={form.frequency}
                  onValueChange={(v) =>
                    setForm((f) => ({ ...f, frequency: v as 'daily' | 'weekly' }))
                  }
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value='daily'>Daily</SelectItem>
                    <SelectItem value='weekly'>Weekly</SelectItem>
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
              <Label>Completes when the user...</Label>
              <Select
                value={form.action_type}
                onValueChange={(v) => setForm((f) => ({ ...f, action_type: v }))}
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {KNOWN_ACTION_TYPES.map((a) => (
                    <SelectItem key={a.value} value={a.value}>
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
