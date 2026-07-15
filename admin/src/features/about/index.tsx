import { useEffect, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import { Loader2 } from 'lucide-react'
import apiClient from '@/lib/api-client'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'

type About = {
  about_title: string
  about_body: string
  about_company: string
  about_company_url: string
}

const QUERY_KEY = 'admin-about'

export function AboutPage() {
  const qc = useQueryClient()
  const [form, setForm] = useState<About | null>(null)

  const { data, isLoading } = useQuery({
    queryKey: [QUERY_KEY],
    queryFn: async () => (await apiClient.get<About>('/admin/about')).data,
  })

  useEffect(() => {
    if (data && !form) setForm(data)
  }, [data, form])

  const save = useMutation({
    mutationFn: async () => apiClient.put<About>('/admin/about', form),
    onSuccess: (res) => {
      qc.setQueryData([QUERY_KEY], res.data)
      toast.success('About the App content saved.')
    },
    onError: (error: any) =>
      toast.error(error?.response?.data?.message ?? 'Could not save.'),
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
        <div>
          <h2 className='text-2xl font-bold tracking-tight'>About the App</h2>
          <p className='text-muted-foreground'>
            Content shown on the app's Settings &gt; About the App screen.
          </p>
        </div>

        {isLoading || !form ? (
          <p className='text-muted-foreground'>Loading...</p>
        ) : (
          <Card className='max-w-2xl'>
            <CardHeader>
              <CardTitle className='text-base'>Content</CardTitle>
              <CardDescription>
                Shown to every user, no app update needed to change it.
              </CardDescription>
            </CardHeader>
            <CardContent className='space-y-4'>
              <div className='space-y-1.5'>
                <Label>Title</Label>
                <Input
                  value={form.about_title}
                  onChange={(e) => setForm((f) => f && { ...f, about_title: e.target.value })}
                />
              </div>
              <div className='space-y-1.5'>
                <Label>Body</Label>
                <Textarea
                  rows={8}
                  value={form.about_body}
                  onChange={(e) => setForm((f) => f && { ...f, about_body: e.target.value })}
                />
                <p className='text-xs text-muted-foreground'>Use a blank line to start a new paragraph.</p>
              </div>
              <div className='grid grid-cols-2 gap-3'>
                <div className='space-y-1.5'>
                  <Label>Company name</Label>
                  <Input
                    value={form.about_company}
                    onChange={(e) => setForm((f) => f && { ...f, about_company: e.target.value })}
                  />
                </div>
                <div className='space-y-1.5'>
                  <Label>Company link</Label>
                  <Input
                    value={form.about_company_url}
                    onChange={(e) => setForm((f) => f && { ...f, about_company_url: e.target.value })}
                  />
                </div>
              </div>
              <Button
                disabled={!form.about_title.trim() || !form.about_body.trim() || save.isPending}
                onClick={() => save.mutate()}
              >
                {save.isPending ? <Loader2 className='animate-spin' /> : null} Save
              </Button>
            </CardContent>
          </Card>
        )}
      </Main>
    </>
  )
}
