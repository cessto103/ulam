import { getRouteApi } from '@tanstack/react-router'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import { ContentReportsDialogs } from './components/content-reports-dialogs'
import { ContentReportsProvider } from './components/content-reports-provider'
import { ContentReportsTable } from './components/content-reports-table'
import { useContentReportsQuery } from './hooks/use-content-reports'

const route = getRouteApi('/_authenticated/content-reports/')

export function ContentReports() {
  const search = route.useSearch()
  const navigate = route.useNavigate()
  const { data, isLoading } = useContentReportsQuery(search)

  return (
    <ContentReportsProvider>
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
              Content Reports
            </h2>
            <p className='text-muted-foreground'>
              Review reported posts, recipes, and stores. Warn, restrict, or
              ban the reported user based on the violation.
            </p>
          </div>
        </div>
        <ContentReportsTable
          data={data?.data ?? []}
          pageCount={data?.last_page ?? 0}
          isLoading={isLoading}
          search={search}
          navigate={navigate}
        />
      </Main>

      <ContentReportsDialogs />
    </ContentReportsProvider>
  )
}
