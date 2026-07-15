import { useEffect, useMemo, useRef, useState } from 'react'
import { marked } from 'marked'
import {
  Archive,
  CheckCircle2,
  Copy,
  Eye,
  FilePlus2,
  Loader2,
  Pencil,
  RotateCcw,
  Trash2,
  Upload,
} from 'lucide-react'
import { toast } from 'sonner'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Textarea } from '@/components/ui/textarea'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import {
  useCreateDraft,
  useDeleteDraft,
  useLegalDocuments,
  useLegalVersion,
  useLegalVersions,
  usePublishVersion,
  useUpdateDraft,
} from './hooks/use-legal'

const statusClass: Record<string, string> = {
  published: 'bg-emerald-500/15 text-emerald-600',
  draft: 'bg-blue-500/15 text-blue-600',
  archived: 'bg-muted text-muted-foreground',
}

const API_ORIGIN = (import.meta.env.VITE_API_URL as string | undefined)?.replace(/\/api\/?$/, '') ?? ''

export function LegalContent() {
  const [slug, setSlug] = useState<'terms' | 'privacy'>('terms')
  const [statusFilter, setStatusFilter] = useState('all')
  const [search, setSearch] = useState('')
  const [viewingId, setViewingId] = useState<number | null>(null)
  const [publishingId, setPublishingId] = useState<number | null>(null)

  const { data: docs } = useLegalDocuments()
  const doc = docs?.find((d) => d.slug === slug)
  const { data: versions, isLoading } = useLegalVersions(slug, {
    status: statusFilter === 'all' ? undefined : statusFilter,
    search: search || undefined,
  })

  const createDraft = useCreateDraft(slug)
  const publish = usePublishVersion()
  const deleteDraft = useDeleteDraft()

  const draft = versions?.find((v) => v.status === 'draft')
  const published = versions?.find((v) => v.status === 'published')

  const startDraft = (duplicateFrom?: number) => {
    if (draft) {
      toast.info('A draft already exists. Edit or delete it first.')
      return
    }
    createDraft.mutate(
      { duplicate_from: duplicateFrom },
      {
        onSuccess: () => toast.success('Draft created.'),
        onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Could not create draft.'),
      }
    )
  }

  return (
    <>
      <Header fixed><Search className='me-auto' /><ThemeSwitch /><ConfigDrawer /><ProfileDropdown /></Header>
      <Main className='flex flex-1 flex-col gap-4 sm:gap-6'>
        <div className='flex flex-wrap items-end justify-between gap-2'>
          <div>
            <h2 className='text-2xl font-bold tracking-tight'>Legal Documents</h2>
            <p className='text-muted-foreground'>
              Terms & Conditions and Privacy Policy: versioned, published from here, accepted in-app by users.
            </p>
          </div>
          <Tabs value={slug} onValueChange={(v) => setSlug(v as 'terms' | 'privacy')}>
            <TabsList>
              <TabsTrigger value='terms'>Terms & Conditions</TabsTrigger>
              <TabsTrigger value='privacy'>Privacy Policy</TabsTrigger>
            </TabsList>
          </Tabs>
        </div>

        {/* Published summary */}
        <div className='grid gap-3 sm:grid-cols-3'>
          <Card>
            <CardHeader className='pb-2'><CardTitle className='text-sm font-medium'>Live version</CardTitle></CardHeader>
            <CardContent>
              <div className='text-2xl font-bold'>{doc?.published_version ? `v${doc.published_version}` : '-'}</div>
              {doc?.published_at && (
                <p className='text-xs text-muted-foreground'>
                  since {new Date(doc.published_at).toLocaleDateString()} by {doc.published_by}
                </p>
              )}
            </CardContent>
          </Card>
          <Card>
            <CardHeader className='pb-2'><CardTitle className='text-sm font-medium'>User acceptances</CardTitle></CardHeader>
            <CardContent><div className='text-2xl font-bold'>{doc?.acceptance_count ?? 0}</div>
              <p className='text-xs text-muted-foreground'>of the live version</p></CardContent>
          </Card>
          <Card>
            <CardHeader className='pb-2'><CardTitle className='text-sm font-medium'>Public page</CardTitle></CardHeader>
            <CardContent>
              <a className='text-sm font-medium text-emerald-700 underline underline-offset-4' href={`${API_ORIGIN}/legal/${slug}`} target='_blank' rel='noreferrer'>
                /legal/{slug}
              </a>
              <p className='text-xs text-muted-foreground'>use this URL on the Play Store listing</p>
            </CardContent>
          </Card>
        </div>

        {/* Draft editor */}
        {draft ? (
          <DraftEditor key={draft.id} draftId={draft.id} onPublish={() => setPublishingId(draft.id)} />
        ) : (
          <div className='flex items-center gap-2 rounded-md border border-dashed p-4'>
            <p className='me-auto text-sm text-muted-foreground'>No open draft for this document.</p>
            <Button variant='outline' onClick={() => startDraft(published?.id)} disabled={createDraft.isPending || !published}>
              <Copy /> New draft from live version
            </Button>
            <Button onClick={() => startDraft()} disabled={createDraft.isPending}>
              <FilePlus2 /> Blank draft
            </Button>
          </div>
        )}

        {/* Version history */}
        <div className='flex flex-wrap items-center justify-between gap-2'>
          <h3 className='text-lg font-semibold'>Version history</h3>
          <div className='flex items-center gap-2'>
            <select className='h-9 rounded-md border bg-background px-3 text-sm' value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)}>
              <option value='all'>All statuses</option>
              <option value='draft'>Draft</option>
              <option value='published'>Published</option>
              <option value='archived'>Archived</option>
            </select>
            <Input placeholder='Search version or changelog…' className='h-9 w-56' value={search} onChange={(e) => setSearch(e.target.value)} />
          </div>
        </div>

        <div className='overflow-hidden rounded-md border'>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Version</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>What's changed</TableHead>
                <TableHead>Author</TableHead>
                <TableHead>Published</TableHead>
                <TableHead>Acceptances</TableHead>
                <TableHead className='text-right'>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {isLoading ? (
                <TableRow><TableCell colSpan={7} className='h-24 text-center text-muted-foreground'>Loading…</TableCell></TableRow>
              ) : (versions ?? []).length === 0 ? (
                <TableRow><TableCell colSpan={7} className='h-24 text-center text-muted-foreground'>No versions found.</TableCell></TableRow>
              ) : (
                versions!.map((v) => (
                  <TableRow key={v.id}>
                    <TableCell className='font-mono'>v{v.version}</TableCell>
                    <TableCell><Badge className={`capitalize ${statusClass[v.status] ?? ''}`}>{v.status}</Badge></TableCell>
                    <TableCell className='max-w-72 truncate' title={v.changelog}>{v.changelog || '-'}</TableCell>
                    <TableCell>{v.author ?? '-'}</TableCell>
                    <TableCell>
                      {v.published_at ? new Date(v.published_at).toLocaleDateString() : '-'}
                      {v.published_by && <div className='text-xs text-muted-foreground'>by {v.published_by}</div>}
                    </TableCell>
                    <TableCell>{v.acceptance_count ?? '-'}</TableCell>
                    <TableCell className='text-right'>
                      <div className='flex justify-end gap-1'>
                        <Button variant='ghost' size='icon' title='View' onClick={() => setViewingId(v.id)}><Eye className='size-4' /></Button>
                        {v.status !== 'draft' && (
                          <Button variant='ghost' size='icon' title='Restore as new draft' onClick={() => startDraft(v.id)}>
                            <RotateCcw className='size-4' />
                          </Button>
                        )}
                        {v.status === 'draft' && (
                          <>
                            <Button variant='ghost' size='icon' title='Publish' onClick={() => setPublishingId(v.id)}>
                              <Upload className='size-4 text-emerald-600' />
                            </Button>
                            <Button
                              variant='ghost' size='icon' title='Delete draft'
                              onClick={() => deleteDraft.mutate(v.id, {
                                onSuccess: () => toast.success('Draft deleted.'),
                                onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Could not delete.'),
                              })}
                            >
                              <Trash2 className='size-4 text-red-500' />
                            </Button>
                          </>
                        )}
                        {v.status === 'archived' && (
                          <Button
                            variant='ghost' size='icon' title='Archive'
                            disabled
                          >
                            <Archive className='size-4 opacity-30' />
                          </Button>
                        )}
                      </div>
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </div>
      </Main>

      <VersionViewDialog id={viewingId} onClose={() => setViewingId(null)} />

      {/* Publish confirmation */}
      <Dialog open={publishingId !== null} onOpenChange={(open) => !open && setPublishingId(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Publish this version?</DialogTitle>
            <DialogDescription>
              The current live version will be archived, and every app user will be required to review and
              accept the new version before continuing to use uLam.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button variant='outline' onClick={() => setPublishingId(null)}>Cancel</Button>
            <Button
              disabled={publish.isPending}
              onClick={() =>
                publish.mutate(publishingId!, {
                  onSuccess: (d: any) => { toast.success(d?.message ?? 'Published.'); setPublishingId(null) },
                  onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Could not publish.'),
                })
              }
            >
              {publish.isPending ? <Loader2 className='animate-spin' /> : <CheckCircle2 />}
              Publish
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  )
}

function DraftEditor({ draftId, onPublish }: { draftId: number; onPublish: () => void }) {
  const { data: draft } = useLegalVersion(draftId)
  const update = useUpdateDraft()

  const [version, setVersion] = useState('')
  const [changelog, setChangelog] = useState('')
  const [content, setContent] = useState('')
  const [preview, setPreview] = useState(false)
  const [saveState, setSaveState] = useState<'saved' | 'saving' | 'dirty'>('saved')
  const loaded = useRef(false)
  const timer = useRef<ReturnType<typeof setTimeout> | null>(null)

  useEffect(() => {
    if (draft && !loaded.current) {
      setVersion(draft.version)
      setChangelog(draft.changelog)
      setContent(draft.content_md ?? '')
      loaded.current = true
    }
  }, [draft])

  // Debounced autosave — 1.5s after the last keystroke.
  const scheduleSave = (next: { version?: string; changelog?: string; content_md?: string }) => {
    setSaveState('dirty')
    if (timer.current) clearTimeout(timer.current)
    timer.current = setTimeout(() => {
      setSaveState('saving')
      update.mutate(
        { id: draftId, ...next },
        {
          onSuccess: () => setSaveState('saved'),
          onError: (e: any) => {
            setSaveState('dirty')
            toast.error(e?.response?.data?.message ?? 'Autosave failed.')
          },
        }
      )
    }, 1500)
  }

  const previewHtml = useMemo(() => (preview ? (marked.parse(content) as string) : ''), [preview, content])

  if (!draft) return null

  return (
    <Card>
      <CardHeader className='flex flex-row flex-wrap items-center justify-between gap-2 pb-3'>
        <CardTitle className='flex items-center gap-2 text-base'>
          <Pencil className='size-4' /> Draft
          <Badge className={statusClass.draft}>v{version || draft.version}</Badge>
          <span className='text-xs font-normal text-muted-foreground'>
            {saveState === 'saved' ? 'All changes saved' : saveState === 'saving' ? 'Saving…' : 'Unsaved changes…'}
          </span>
        </CardTitle>
        <div className='flex items-center gap-2'>
          <Button variant='outline' size='sm' onClick={() => setPreview((p) => !p)}>
            <Eye /> {preview ? 'Edit' : 'Preview'}
          </Button>
          <Button size='sm' onClick={onPublish}><Upload /> Publish…</Button>
        </div>
      </CardHeader>
      <CardContent className='space-y-3'>
        <div className='grid gap-3 sm:grid-cols-[8rem_1fr]'>
          <div className='space-y-1.5'>
            <Label>Version</Label>
            <Input value={version} onChange={(e) => { setVersion(e.target.value); scheduleSave({ version: e.target.value }) }} placeholder='1.1.0' />
          </div>
          <div className='space-y-1.5'>
            <Label>What's changed (required to publish)</Label>
            <Input value={changelog} onChange={(e) => { setChangelog(e.target.value); scheduleSave({ changelog: e.target.value }) }} placeholder='• Updated refund policy • Revised privacy section' />
          </div>
        </div>
        {preview ? (
          <div
            className='prose prose-sm dark:prose-invert max-h-[480px] max-w-none overflow-y-auto rounded-md border p-4'
            dangerouslySetInnerHTML={{ __html: previewHtml }}
          />
        ) : (
          <Textarea
            value={content}
            onChange={(e) => { setContent(e.target.value); scheduleSave({ content_md: e.target.value }) }}
            className='min-h-[420px] font-mono text-xs'
            placeholder='# Document title…'
          />
        )}
      </CardContent>
    </Card>
  )
}

function VersionViewDialog({ id, onClose }: { id: number | null; onClose: () => void }) {
  const { data: v } = useLegalVersion(id)
  const html = useMemo(() => (v?.content_md ? (marked.parse(v.content_md) as string) : ''), [v?.content_md])

  return (
    <Dialog open={id !== null} onOpenChange={(open) => !open && onClose()}>
      <DialogContent className='max-w-3xl'>
        <DialogHeader>
          <DialogTitle>
            {v ? `v${v.version}` : ''}{' '}
            {v && <Badge className={`ms-2 capitalize ${statusClass[v.status] ?? ''}`}>{v.status}</Badge>}
          </DialogTitle>
          <DialogDescription>{v?.changelog}</DialogDescription>
        </DialogHeader>
        <div
          className='prose prose-sm dark:prose-invert max-h-[60vh] max-w-none overflow-y-auto rounded-md border p-4'
          dangerouslySetInnerHTML={{ __html: html }}
        />
      </DialogContent>
    </Dialog>
  )
}
