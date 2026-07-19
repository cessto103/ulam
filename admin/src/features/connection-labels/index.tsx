import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import { Pencil, Plus, Trash2 } from 'lucide-react'
import apiClient from '@/lib/api-client'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
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

type ConnectionLabel = {
  id: number
  name: string
  sort_order: number
  is_active: boolean
}

type LabelForm = {
  name: string
  sort_order: number
  is_active: boolean
}

const EMPTY_FORM: LabelForm = {
  name: '',
  sort_order: 0,
  is_active: true,
}

const QUERY_KEY = 'admin-connection-labels'

export function ConnectionLabels() {
  const qc = useQueryClient()
  const [editing, setEditing] = useState<ConnectionLabel | 'new' | null>(null)
  const [deleting, setDeleting] = useState<ConnectionLabel | null>(null)
  const [form, setForm] = useState<LabelForm>(EMPTY_FORM)

  const { data, isLoading } = useQuery({
    queryKey: [QUERY_KEY],
    queryFn: async () => {
      const { data } = await apiClient.get<{ labels: ConnectionLabel[] }>(
        '/admin/connection-labels'
      )
      return data.labels
    },
  })

  const save = useMutation({
    mutationFn: async () => {
      if (editing === 'new') {
        return apiClient.post('/admin/connection-labels', form)
      }
      return apiClient.patch(
        `/admin/connection-labels/${(editing as ConnectionLabel).id}`,
        form
      )
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: [QUERY_KEY] })
      toast.success('Label saved.')
      setEditing(null)
    },
    onError: (error: any) =>
      toast.error(error?.response?.data?.message ?? 'Could not save label.'),
  })

  const remove = useMutation({
    mutationFn: async (id: number) =>
      apiClient.delete(`/admin/connection-labels/${id}`),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: [QUERY_KEY] })
      toast.success('Label deleted.')
      setDeleting(null)
    },
  })

  const openEditor = (label: ConnectionLabel | 'new') => {
    setForm(
      label === 'new'
        ? EMPTY_FORM
        : {
            name: label.name,
            sort_order: label.sort_order,
            is_active: label.is_active,
          }
    )
    setEditing(label)
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
              Connection Labels
            </h2>
            <p className='text-muted-foreground'>
              The relationship options a user can put on an accepted
              connection (e.g. Household, Relative). Labels are private to
              the person who set them; the other side never sees them.
              Deleting a label simply clears it from any connection using it.
            </p>
          </div>
          <Button onClick={() => openEditor('new')}>
            <Plus className='me-1 size-4' /> Add Label
          </Button>
        </div>

        <div className='flex flex-col gap-2'>
          {isLoading ? (
            <p className='py-12 text-center text-muted-foreground'>
              Loading...
            </p>
          ) : (data ?? []).length === 0 ? (
            <p className='py-12 text-center text-muted-foreground'>
              No labels yet.
            </p>
          ) : (
            (data ?? []).map((label) => (
              <div
                key={label.id}
                className='flex items-center justify-between gap-3 rounded-md border p-3'
              >
                <div className='flex min-w-0 flex-wrap items-center gap-2'>
                  <span className='font-medium'>{label.name}</span>
                  <Badge variant='outline'>sort {label.sort_order}</Badge>
                  {!label.is_active && (
                    <Badge className='bg-muted text-muted-foreground'>
                      Inactive
                    </Badge>
                  )}
                </div>
                <div className='flex shrink-0 gap-1'>
                  <Button
                    size='icon'
                    variant='ghost'
                    className='size-8'
                    onClick={() => openEditor(label)}
                  >
                    <Pencil className='size-4' />
                  </Button>
                  <Button
                    size='icon'
                    variant='ghost'
                    className='size-8 text-destructive'
                    onClick={() => setDeleting(label)}
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
        <DialogContent className='sm:max-w-md'>
          <DialogHeader>
            <DialogTitle>
              {editing === 'new' ? 'Add Label' : 'Edit Label'}
            </DialogTitle>
          </DialogHeader>
          <div className='space-y-3'>
            <div className='space-y-1.5'>
              <Label>Name</Label>
              <Input
                value={form.name}
                onChange={(e) =>
                  setForm((f) => ({ ...f, name: e.target.value }))
                }
                placeholder='e.g. Co-worker'
              />
            </div>
            <div className='space-y-1.5'>
              <Label>Sort order</Label>
              <Input
                type='number'
                min={0}
                value={form.sort_order}
                onChange={(e) =>
                  setForm((f) => ({ ...f, sort_order: Number(e.target.value) }))
                }
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
              disabled={!form.name.trim() || save.isPending}
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
        title='Delete this label?'
        destructive
        desc={deleting?.name ?? ''}
        confirmText='Delete'
        isLoading={remove.isPending}
        handleConfirm={() => deleting && remove.mutate(deleting.id)}
      />
    </>
  )
}
