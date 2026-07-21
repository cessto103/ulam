import { useState } from 'react'
import { Loader2 } from 'lucide-react'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import { emailTemplateSlugs, TEMPLATE_META } from './data/schema'
import { EmailTemplateEditor } from './components/email-template-editor'
import { useEmailTemplatesQuery } from './hooks/use-email-templates'

export function EmailTemplates() {
  const { data: templates, isLoading } = useEmailTemplatesQuery()
  const [active, setActive] = useState<string>(emailTemplateSlugs[0])

  return (
    <>
      <Header fixed><Search className='me-auto' /><ThemeSwitch /><ConfigDrawer /><ProfileDropdown /></Header>
      <Main className='flex flex-1 flex-col gap-4'>
        <div>
          <h2 className='text-2xl font-bold tracking-tight'>Email Templates</h2>
          <p className='text-muted-foreground'>
            Edit the wording, logo, and images for onboarding and verification-code emails. Colors and
            layout stay fixed so every email stays on-brand.
          </p>
        </div>

        {isLoading || !templates ? (
          <Loader2 className='animate-spin text-muted-foreground' />
        ) : (
          <Tabs value={active} onValueChange={setActive}>
            <div className='flex flex-col gap-4 md:flex-row md:items-start'>
              <TabsList className='h-auto w-full flex-col items-stretch justify-start gap-1 bg-transparent p-0 md:w-64 md:shrink-0'>
                {emailTemplateSlugs.map((slug) => (
                  <TabsTrigger
                    key={slug}
                    value={slug}
                    className='w-full justify-start whitespace-normal rounded-md border-none px-3 py-2 text-left text-sm font-medium data-[state=active]:bg-muted data-[state=active]:shadow-none'
                  >
                    {TEMPLATE_META[slug].label}
                  </TabsTrigger>
                ))}
              </TabsList>

              <div className='min-w-0 flex-1'>
                {emailTemplateSlugs.map((slug) => {
                  const template = templates.find((t) => t.slug === slug)
                  return (
                    <TabsContent key={slug} value={slug} className='mt-0'>
                      {template && <EmailTemplateEditor template={template} />}
                    </TabsContent>
                  )
                })}
              </div>
            </div>
          </Tabs>
        )}
      </Main>
    </>
  )
}
