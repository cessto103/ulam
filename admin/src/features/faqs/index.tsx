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

type Faq = {
  id: number
  question: string
  question_tl: string | null
  answer: string
  answer_tl: string | null
  category: string | null
  sort: number
  is_published: boolean
}

type FaqForm = {
  question: string
  question_tl: string
  answer: string
  answer_tl: string
  category: string
  sort: number
  is_published: boolean
}

const EMPTY_FORM: FaqForm = {
  question: '',
  question_tl: '',
  answer: '',
  answer_tl: '',
  category: '',
  sort: 0,
  is_published: true,
}

const QUERY_KEY = 'admin-faqs'

export function Faqs() {
  const qc = useQueryClient()
  const [editing, setEditing] = useState<Faq | 'new' | null>(null)
  const [deleting, setDeleting] = useState<Faq | null>(null)
  const [form, setForm] = useState<FaqForm>(EMPTY_FORM)

  const { data, isLoading } = useQuery({
    queryKey: [QUERY_KEY],
    queryFn: async () => {
      const { data } = await apiClient.get<{ faqs: Faq[] }>('/admin/faqs')
      return data.faqs
    },
  })

  const save = useMutation({
    mutationFn: async () => {
      const body = {
        ...form,
        question_tl: form.question_tl || null,
        answer_tl: form.answer_tl || null,
        category: form.category || null,
      }
      if (editing === 'new') {
        return apiClient.post('/admin/faqs', body)
      }
      return apiClient.patch(`/admin/faqs/${(editing as Faq).id}`, body)
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: [QUERY_KEY] })
      toast.success('FAQ saved.')
      setEditing(null)
    },
    onError: (error: any) =>
      toast.error(error?.response?.data?.message ?? 'Could not save FAQ.'),
  })

  const remove = useMutation({
    mutationFn: async (id: number) => apiClient.delete(`/admin/faqs/${id}`),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: [QUERY_KEY] })
      toast.success('FAQ deleted.')
      setDeleting(null)
    },
  })

  const openEditor = (faq: Faq | 'new') => {
    setForm(
      faq === 'new'
        ? { ...EMPTY_FORM, sort: (data?.length ?? 0) }
        : {
            question: faq.question,
            question_tl: faq.question_tl ?? '',
            answer: faq.answer,
            answer_tl: faq.answer_tl ?? '',
            category: faq.category ?? '',
            sort: faq.sort,
            is_published: faq.is_published,
          }
    )
    setEditing(faq)
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
            <h2 className='text-2xl font-bold tracking-tight'>FAQs</h2>
            <p className='text-muted-foreground'>
              Questions shown in the app's Help & Support screen. Tagalog
              fields are optional — the app falls back to English.
            </p>
          </div>
          <Button onClick={() => openEditor('new')}>
            <Plus className='me-1 size-4' /> Add FAQ
          </Button>
        </div>

        <div className='flex flex-col gap-2'>
          {isLoading ? (
            <p className='py-12 text-center text-muted-foreground'>
              Loading...
            </p>
          ) : (data ?? []).length === 0 ? (
            <p className='py-12 text-center text-muted-foreground'>
              No FAQs yet.
            </p>
          ) : (
            (data ?? []).map((faq) => (
              <div
                key={faq.id}
                className='flex items-start justify-between gap-3 rounded-md border p-3'
              >
                <div className='min-w-0'>
                  <div className='flex flex-wrap items-center gap-2'>
                    <span className='font-medium'>{faq.question}</span>
                    {faq.category && (
                      <Badge variant='outline' className='capitalize'>
                        {faq.category}
                      </Badge>
                    )}
                    {!faq.is_published && (
                      <Badge className='bg-muted text-muted-foreground'>
                        Draft
                      </Badge>
                    )}
                  </div>
                  <p className='mt-0.5 line-clamp-2 text-sm text-muted-foreground'>
                    {faq.answer}
                  </p>
                </div>
                <div className='flex shrink-0 gap-1'>
                  <Button
                    size='icon'
                    variant='ghost'
                    className='size-8'
                    onClick={() => openEditor(faq)}
                  >
                    <Pencil className='size-4' />
                  </Button>
                  <Button
                    size='icon'
                    variant='ghost'
                    className='size-8 text-destructive'
                    onClick={() => setDeleting(faq)}
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
              {editing === 'new' ? 'Add FAQ' : 'Edit FAQ'}
            </DialogTitle>
          </DialogHeader>
          <div className='space-y-3'>
            <div className='space-y-1.5'>
              <Label>Question (English)</Label>
              <Input
                value={form.question}
                onChange={(e) =>
                  setForm((f) => ({ ...f, question: e.target.value }))
                }
              />
            </div>
            <div className='space-y-1.5'>
              <Label>Question (Tagalog)</Label>
              <Input
                value={form.question_tl}
                onChange={(e) =>
                  setForm((f) => ({ ...f, question_tl: e.target.value }))
                }
              />
            </div>
            <div className='space-y-1.5'>
              <Label>Answer (English)</Label>
              <Textarea
                rows={3}
                value={form.answer}
                onChange={(e) =>
                  setForm((f) => ({ ...f, answer: e.target.value }))
                }
              />
            </div>
            <div className='space-y-1.5'>
              <Label>Answer (Tagalog)</Label>
              <Textarea
                rows={3}
                value={form.answer_tl}
                onChange={(e) =>
                  setForm((f) => ({ ...f, answer_tl: e.target.value }))
                }
              />
            </div>
            <div className='grid grid-cols-2 gap-3'>
              <div className='space-y-1.5'>
                <Label>Category</Label>
                <Input
                  placeholder='payment, subscription...'
                  value={form.category}
                  onChange={(e) =>
                    setForm((f) => ({ ...f, category: e.target.value }))
                  }
                />
              </div>
              <div className='space-y-1.5'>
                <Label>Sort order</Label>
                <Input
                  type='number'
                  min={0}
                  value={form.sort}
                  onChange={(e) =>
                    setForm((f) => ({ ...f, sort: Number(e.target.value) }))
                  }
                />
              </div>
            </div>
            <div className='flex items-center justify-between rounded-md border p-3'>
              <Label>Published</Label>
              <Switch
                checked={form.is_published}
                onCheckedChange={(v) =>
                  setForm((f) => ({ ...f, is_published: v }))
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
                !form.question.trim() || !form.answer.trim() || save.isPending
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
        title='Delete this FAQ?'
        destructive
        desc={deleting?.question ?? ''}
        confirmText='Delete'
        isLoading={remove.isPending}
        handleConfirm={() => deleting && remove.mutate(deleting.id)}
      />
    </>
  )
}
