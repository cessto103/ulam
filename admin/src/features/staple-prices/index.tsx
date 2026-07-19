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

type StaplePrice = {
  id: number
  item_name: string
  unit: string
  price: number
  is_active: boolean
}

type StapleForm = {
  item_name: string
  unit: string
  price: number
  is_active: boolean
}

const EMPTY_FORM: StapleForm = {
  item_name: '',
  unit: 'sachet',
  price: 10,
  is_active: true,
}

const QUERY_KEY = 'admin-staple-prices'

export function StaplePrices() {
  const qc = useQueryClient()
  const [editing, setEditing] = useState<StaplePrice | 'new' | null>(null)
  const [deleting, setDeleting] = useState<StaplePrice | null>(null)
  const [form, setForm] = useState<StapleForm>(EMPTY_FORM)

  const { data, isLoading } = useQuery({
    queryKey: [QUERY_KEY],
    queryFn: async () => {
      const { data } = await apiClient.get<{ staples: StaplePrice[] }>(
        '/admin/staple-prices'
      )
      return data.staples
    },
  })

  const save = useMutation({
    mutationFn: async () => {
      if (editing === 'new') {
        return apiClient.post('/admin/staple-prices', form)
      }
      return apiClient.patch(
        `/admin/staple-prices/${(editing as StaplePrice).id}`,
        form
      )
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: [QUERY_KEY] })
      toast.success('Staple saved.')
      setEditing(null)
    },
    onError: (error: any) =>
      toast.error(error?.response?.data?.message ?? 'Could not save staple.'),
  })

  const remove = useMutation({
    mutationFn: async (id: number) =>
      apiClient.delete(`/admin/staple-prices/${id}`),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: [QUERY_KEY] })
      toast.success('Staple deleted.')
      setDeleting(null)
    },
  })

  const openEditor = (staple: StaplePrice | 'new') => {
    setForm(
      staple === 'new'
        ? EMPTY_FORM
        : {
            item_name: staple.item_name,
            unit: staple.unit,
            price: staple.price,
            is_active: staple.is_active,
          }
    )
    setEditing(staple)
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
              Tingi Staple Prices
            </h2>
            <p className='text-muted-foreground'>
              When a meal-plan ingredient name matches an item here, its
              shopping list line uses this tingi price and unit (the smallest
              amount actually sold, like a sachet or takal) instead of the
              recipe's proportional estimate, keeping the recipe amount as a
              note. Broad names match broadly: "Toyo" also matches "Toyo (soy
              sauce)". Meal plan cost estimates are never affected.
            </p>
          </div>
          <Button onClick={() => openEditor('new')}>
            <Plus className='me-1 size-4' /> Add Staple
          </Button>
        </div>

        <div className='flex flex-col gap-2'>
          {isLoading ? (
            <p className='py-12 text-center text-muted-foreground'>
              Loading...
            </p>
          ) : (data ?? []).length === 0 ? (
            <p className='py-12 text-center text-muted-foreground'>
              No staples yet.
            </p>
          ) : (
            (data ?? []).map((staple) => (
              <div
                key={staple.id}
                className='flex items-center justify-between gap-3 rounded-md border p-3'
              >
                <div className='flex min-w-0 flex-wrap items-center gap-2'>
                  <span className='font-medium'>{staple.item_name}</span>
                  <Badge variant='secondary'>
                    ₱{Number(staple.price).toFixed(2)} / {staple.unit}
                  </Badge>
                  {!staple.is_active && (
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
                    onClick={() => openEditor(staple)}
                  >
                    <Pencil className='size-4' />
                  </Button>
                  <Button
                    size='icon'
                    variant='ghost'
                    className='size-8 text-destructive'
                    onClick={() => setDeleting(staple)}
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
              {editing === 'new' ? 'Add Staple' : 'Edit Staple'}
            </DialogTitle>
          </DialogHeader>
          <div className='space-y-3'>
            <div className='space-y-1.5'>
              <Label>Item name</Label>
              <Input
                value={form.item_name}
                onChange={(e) =>
                  setForm((f) => ({ ...f, item_name: e.target.value }))
                }
                placeholder='e.g. Toyo'
              />
            </div>
            <div className='grid grid-cols-2 gap-3'>
              <div className='space-y-1.5'>
                <Label>Unit</Label>
                <Input
                  value={form.unit}
                  onChange={(e) =>
                    setForm((f) => ({ ...f, unit: e.target.value }))
                  }
                  placeholder='sachet, takal...'
                />
              </div>
              <div className='space-y-1.5'>
                <Label>Price (₱)</Label>
                <Input
                  type='number'
                  min={0}
                  step='0.25'
                  value={form.price}
                  onChange={(e) =>
                    setForm((f) => ({ ...f, price: Number(e.target.value) }))
                  }
                />
              </div>
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
              disabled={!form.item_name.trim() || !form.unit.trim() || save.isPending}
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
        title='Delete this staple?'
        destructive
        desc={deleting?.item_name ?? ''}
        confirmText='Delete'
        isLoading={remove.isPending}
        handleConfirm={() => deleting && remove.mutate(deleting.id)}
      />
    </>
  )
}
