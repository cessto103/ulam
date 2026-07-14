import { useMemo } from 'react'
import { useQuery } from '@tanstack/react-query'
import { marked } from 'marked'
import { Loader2 } from 'lucide-react'
import apiClient from '@/lib/api-client'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'

export function TechnicalGuide() {
  const { data, isLoading } = useQuery({
    queryKey: ['admin-technical-guide'],
    queryFn: async () =>
      (await apiClient.get<{ content_md: string; updated_at: string }>('/admin/technical-guide')).data,
    staleTime: 5 * 60_000,
  })

  const html = useMemo(() => (data?.content_md ? (marked.parse(data.content_md) as string) : ''), [data?.content_md])

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
          <article
            className='prose prose-sm dark:prose-invert max-w-3xl rounded-md border bg-background p-6 [&_code]:rounded [&_code]:bg-muted [&_code]:px-1 [&_pre]:overflow-x-auto [&_pre]:rounded-md [&_pre]:bg-muted [&_pre]:p-3 [&_table]:w-full [&_td]:border [&_td]:p-2 [&_th]:border [&_th]:bg-muted/50 [&_th]:p-2'
            dangerouslySetInnerHTML={{ __html: html }}
          />
        )}
      </Main>
    </>
  )
}
