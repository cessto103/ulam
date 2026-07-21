import { useEffect, useMemo, useRef, useState } from 'react'
import { marked } from 'marked'
import { ImageUp, Send } from 'lucide-react'
import { toast } from 'sonner'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { TEMPLATE_META, type EmailTemplate } from '../data/schema'
import { useSendTestEmail, useUpdateEmailTemplate, useUploadEmailImage } from '../hooks/use-email-templates'

/** Inserts `text` at the current cursor position of a textarea (replacing any selection). */
function insertAtCursor(el: HTMLTextAreaElement, text: string): string {
  const start = el.selectionStart ?? el.value.length
  const end = el.selectionEnd ?? el.value.length
  return el.value.slice(0, start) + text + el.value.slice(end)
}

export function EmailTemplateEditor({ template }: { template: EmailTemplate }) {
  const meta = TEMPLATE_META[template.slug]
  const update = useUpdateEmailTemplate()
  const uploadImage = useUploadEmailImage()
  const sendTest = useSendTestEmail()

  const [subject, setSubject] = useState(template.subject)
  const [introMd, setIntroMd] = useState(template.intro_md)
  const [noteMd, setNoteMd] = useState(template.note_md ?? '')
  const [ctaLabel, setCtaLabel] = useState(template.cta_label ?? '')

  const introRef = useRef<HTMLTextAreaElement>(null)
  const noteRef = useRef<HTMLTextAreaElement>(null)
  const introFileRef = useRef<HTMLInputElement>(null)

  useEffect(() => {
    setSubject(template.subject)
    setIntroMd(template.intro_md)
    setNoteMd(template.note_md ?? '')
    setCtaLabel(template.cta_label ?? '')
  }, [template])

  const dirty =
    subject !== template.subject ||
    introMd !== template.intro_md ||
    noteMd !== (template.note_md ?? '') ||
    ctaLabel !== (template.cta_label ?? '')

  const introHtml = useMemo(() => marked.parse(introMd || '') as string, [introMd])
  const noteHtml = useMemo(() => marked.parse(noteMd || '') as string, [noteMd])

  const save = () => {
    update.mutate(
      {
        slug: template.slug,
        subject,
        intro_md: introMd,
        note_md: noteMd || null,
        cta_label: ctaLabel || null,
      },
      {
        onSuccess: () => toast.success('Template saved.'),
        onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Could not save.'),
      }
    )
  }

  const handleInsertImage = async (file: File) => {
    try {
      const url = await uploadImage.mutateAsync(file)
      const el = introRef.current
      if (el) {
        setIntroMd(insertAtCursor(el, `![](${url})`))
      } else {
        setIntroMd((prev) => `${prev}\n\n![](${url})`)
      }
      toast.success('Image uploaded and inserted.')
    } catch (e: any) {
      toast.error(e?.response?.data?.message ?? 'Upload failed.')
    } finally {
      if (introFileRef.current) introFileRef.current.value = ''
    }
  }

  return (
    <div className='grid gap-4 lg:grid-cols-[1fr_360px]'>
      <Card>
        <CardHeader>
          <CardTitle className='flex flex-wrap items-center gap-2 text-base'>
            {meta.label}
            <span className='flex gap-1'>
              {meta.placeholders.map((p) => (
                <Badge key={p} variant='outline' className='font-mono text-xs'>
                  {p}
                </Badge>
              ))}
            </span>
          </CardTitle>
          <p className='text-sm text-muted-foreground'>{meta.description}</p>
        </CardHeader>
        <CardContent className='space-y-4'>
          <div className='space-y-1.5'>
            <Label>Subject</Label>
            <Input value={subject} onChange={(e) => setSubject(e.target.value)} />
          </div>

          <div className='space-y-1.5'>
            <div className='flex items-center justify-between'>
              <Label>
                Message{' '}
                {meta.hasCode && (
                  <span className='font-normal text-muted-foreground'>
                    (shown above the code — the code itself is always fixed)
                  </span>
                )}
              </Label>
              <input
                ref={introFileRef}
                type='file'
                accept='image/png,image/jpeg,image/webp,image/gif'
                className='hidden'
                onChange={(e) => e.target.files?.[0] && handleInsertImage(e.target.files[0])}
              />
              <Button
                type='button'
                variant='outline'
                size='sm'
                disabled={uploadImage.isPending}
                onClick={() => introFileRef.current?.click()}
              >
                <ImageUp /> Insert image
              </Button>
            </div>
            <Textarea ref={introRef} rows={8} value={introMd} onChange={(e) => setIntroMd(e.target.value)} />
            <p className='text-xs text-muted-foreground'>
              Supports markdown: **bold**, - lists, and images. Written text only — no raw HTML.
            </p>
          </div>

          {meta.hasCta && (
            <div className='space-y-1.5'>
              <Label>Button label</Label>
              <Input
                value={ctaLabel}
                onChange={(e) => setCtaLabel(e.target.value)}
                placeholder='Leave blank to hide the button'
              />
            </div>
          )}

          <div className='space-y-1.5'>
            <Label>Note {meta.hasCode ? '(shown below the code)' : '(shown after the message)'}</Label>
            <Textarea ref={noteRef} rows={3} value={noteMd} onChange={(e) => setNoteMd(e.target.value)} />
          </div>

          <div className='flex items-center justify-between border-t pt-4'>
            <Button variant='outline' size='sm' disabled={sendTest.isPending} onClick={() =>
              sendTest.mutate(template.slug, {
                onSuccess: (message) => toast.success(message),
                onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Could not send test.'),
              })
            }>
              <Send /> Send test to myself
            </Button>
            <Button disabled={!dirty || update.isPending} onClick={save}>
              Save changes
            </Button>
          </div>
          {dirty && (
            <p className='text-xs text-muted-foreground'>
              "Send test" sends the last saved version — save first to test your latest edits.
            </p>
          )}
        </CardContent>
      </Card>

      <Card className='h-fit'>
        <CardHeader>
          <CardTitle className='text-sm'>Preview</CardTitle>
        </CardHeader>
        <CardContent>
          <div className='overflow-hidden rounded-xl border bg-white text-black shadow-sm'>
            <div className='bg-[#E7653B] p-6 text-center text-lg font-extrabold text-white'>uLam</div>
            <div className='space-y-3 p-5 text-sm'>
              <p className='font-bold'>Hi Cess,</p>
              <div
                className='prose prose-sm max-w-none [&_li]:text-sm [&_p]:text-sm'
                dangerouslySetInnerHTML={{ __html: introHtml }}
              />
              {meta.hasCode && (
                <div className='rounded-lg bg-[#FFF8E8] py-3 text-center font-mono text-2xl font-extrabold tracking-[6px] text-[#386641]'>
                  123456
                </div>
              )}
              {meta.hasCta && ctaLabel && (
                <div className='text-center'>
                  <span className='inline-block rounded-md bg-[#16a34a] px-5 py-2 text-xs font-semibold text-white'>
                    {ctaLabel}
                  </span>
                </div>
              )}
              {noteMd && (
                <div
                  className='prose prose-sm max-w-none text-muted-foreground [&_p]:text-xs'
                  dangerouslySetInnerHTML={{ __html: noteHtml }}
                />
              )}
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
