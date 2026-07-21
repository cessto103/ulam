import { useMemo, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { marked } from 'marked'
import { Loader2 } from 'lucide-react'
import apiClient from '@/lib/api-client'
import { cn } from '@/lib/utils'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'

const PROSE_CLASSES =
  'prose prose-sm dark:prose-invert max-w-3xl [&_code]:rounded [&_code]:bg-muted [&_code]:px-1 [&_pre]:overflow-x-auto [&_pre]:rounded-md [&_pre]:bg-muted [&_pre]:p-3 [&_table]:w-full [&_td]:border [&_td]:p-2 [&_th]:border [&_th]:bg-muted/50 [&_th]:p-2'

/** Strips a trailing markdown `---` rule (used between sections in the source
 * file) so it doesn't render as a stray divider at the bottom of each tab. */
function trimTrailingRule(md: string): string {
  return md.replace(/\n{0,2}-{3,}\s*$/, '').trim()
}

/** Splits the guide on top-level `## ` headings (not `### `) into an intro
 * block plus one { title, body } per section, each topic becoming its own
 * vertical tab instead of one long scrolling page. */
function splitSections(md: string): { introMd: string; sections: { title: string; bodyMd: string }[] } {
  const lines = md.split('\n')
  const intro: string[] = []
  const sections: { title: string; body: string[] }[] = []
  let current: { title: string; body: string[] } | null = null

  for (const line of lines) {
    const heading = /^## (.+)$/.exec(line)
    if (heading) {
      if (current) sections.push(current)
      current = { title: heading[1].trim(), body: [] }
    } else if (current) {
      current.body.push(line)
    } else {
      intro.push(line)
    }
  }
  if (current) sections.push(current)

  return {
    introMd: trimTrailingRule(intro.join('\n')),
    sections: sections.map((s) => ({ title: s.title, bodyMd: trimTrailingRule(s.body.join('\n')) })),
  }
}

export function TechnicalGuide() {
  const { data, isLoading } = useQuery({
    queryKey: ['admin-technical-guide'],
    queryFn: async () =>
      (await apiClient.get<{ content_md: string; updated_at: string }>('/admin/technical-guide')).data,
    staleTime: 5 * 60_000,
  })

  const { introMd, sections } = useMemo(
    () => (data?.content_md ? splitSections(data.content_md) : { introMd: '', sections: [] }),
    [data?.content_md]
  )
  const introHtml = useMemo(() => (introMd ? (marked.parse(introMd) as string) : ''), [introMd])
  const sectionHtml = useMemo(
    () => sections.map((s) => marked.parse(s.bodyMd) as string),
    [sections]
  )

  const [active, setActive] = useState('0')

  return (
    <>
      <Header fixed><Search className='me-auto' /><ThemeSwitch /><ConfigDrawer /><ProfileDropdown /></Header>
      <Main className='flex flex-1 flex-col gap-4'>
        <div>
          <h2 className='text-2xl font-bold tracking-tight'>Technical Guide</h2>
          <p className='text-muted-foreground'>
            Operations reference (cron, deploys, env, security, builds). Rendered live from{' '}
            <code className='rounded bg-muted px-1'>TECHNICAL.md</code> in the backend repo
            {data?.updated_at ? ` · last updated ${new Date(data.updated_at).toLocaleString()}` : ''}.
          </p>
        </div>

        {isLoading ? (
          <Loader2 className='animate-spin text-muted-foreground' />
        ) : (
          <>
            {introMd && (
              <article
                className={cn(PROSE_CLASSES, 'rounded-md border bg-background p-6')}
                dangerouslySetInnerHTML={{ __html: introHtml }}
              />
            )}

            <Tabs value={active} onValueChange={setActive}>
              <div className='flex flex-col gap-4 md:flex-row md:items-start'>
                <TabsList className='h-auto w-full flex-col items-stretch justify-start gap-1 bg-transparent p-0 md:w-72 md:shrink-0'>
                  {sections.map((s, i) => (
                    <TabsTrigger
                      key={i}
                      value={String(i)}
                      className='w-full justify-start whitespace-normal rounded-md border-none px-3 py-2 text-left text-sm font-medium data-[state=active]:bg-muted data-[state=active]:shadow-none'
                    >
                      {s.title}
                    </TabsTrigger>
                  ))}
                </TabsList>

                <div className='min-w-0 flex-1'>
                  {sections.map((_, i) => (
                    <TabsContent key={i} value={String(i)} className='mt-0'>
                      <article
                        className={cn(PROSE_CLASSES, 'rounded-md border bg-background p-6')}
                        dangerouslySetInnerHTML={{ __html: sectionHtml[i] }}
                      />
                    </TabsContent>
                  ))}
                </div>
              </div>
            </Tabs>
          </>
        )}
      </Main>
    </>
  )
}
