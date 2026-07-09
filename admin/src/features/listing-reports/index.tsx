import { getRouteApi } from '@tanstack/react-router'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import { ListingReportsDialogs } from './components/listing-reports-dialogs'
import { ListingReportsProvider } from './components/listing-reports-provider'
import { ListingReportsTable } from './components/listing-reports-table'
import { useListingReportsQuery } from './hooks/use-listing-reports'

const route = getRouteApi('/_authenticated/listing-reports/')

export function ListingReports() {
  const search = route.useSearch()
  const navigate = route.useNavigate()
  const { data, isLoading } = useListingReportsQuery(search)

  return (
    <ListingReportsProvider>
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
              Reported Listings
            </h2>
            <p className='text-muted-foreground'>
              Review and moderate reported markets and stores.
            </p>
          </div>
        </div>
        <ListingReportsTable
          data={data?.data ?? []}
          pageCount={data?.last_page ?? 0}
          isLoading={isLoading}
          search={search}
          navigate={navigate}
        />
      </Main>

      <ListingReportsDialogs />
    </ListingReportsProvider>
  )
}
