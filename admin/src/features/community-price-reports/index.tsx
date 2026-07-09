import { getRouteApi } from '@tanstack/react-router'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import { CommunityPriceReportsDialogs } from './components/community-price-reports-dialogs'
import { CommunityPriceReportsPrimaryButtons } from './components/community-price-reports-primary-buttons'
import { CommunityPriceReportsProvider } from './components/community-price-reports-provider'
import { CommunityPriceReportsTable } from './components/community-price-reports-table'
import { useCommunityPriceReportsQuery } from './hooks/use-community-price-reports'

const route = getRouteApi('/_authenticated/community-price-reports/')

export function CommunityPriceReports() {
  const search = route.useSearch()
  const navigate = route.useNavigate()
  const { data, isLoading } = useCommunityPriceReportsQuery(search)

  return (
    <CommunityPriceReportsProvider>
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
              Community Price Reports
            </h2>
            <p className='text-muted-foreground'>
              Review and moderate crowd-sourced price reports.
            </p>
          </div>
          <CommunityPriceReportsPrimaryButtons />
        </div>
        <CommunityPriceReportsTable
          data={data?.data ?? []}
          pageCount={data?.last_page ?? 0}
          isLoading={isLoading}
          search={search}
          navigate={navigate}
        />
      </Main>

      <CommunityPriceReportsDialogs />
    </CommunityPriceReportsProvider>
  )
}
