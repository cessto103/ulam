import { getRouteApi } from '@tanstack/react-router'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import { MarketsDialogs } from './components/markets-dialogs'
import { MarketsPrimaryButtons } from './components/markets-primary-buttons'
import { MarketsProvider } from './components/markets-provider'
import { MarketsTable } from './components/markets-table'
import { useMarketsQuery } from './hooks/use-markets'

const route = getRouteApi('/_authenticated/markets/')

export function Markets() {
  const search = route.useSearch()
  const navigate = route.useNavigate()
  const { data, isLoading } = useMarketsQuery(search)

  return (
    <MarketsProvider>
      <Header fixed>
        <Search className='me-auto' />
        <ThemeSwitch />
        <ConfigDrawer />
        <ProfileDropdown />
      </Header>

      <Main className='flex flex-1 flex-col gap-4 sm:gap-6'>
        <div className='flex flex-wrap items-end justify-between gap-2'>
          <div>
            <h2 className='text-2xl font-bold tracking-tight'>Markets</h2>
            <p className='text-muted-foreground'>
              Manage wet markets, palengke, supermarkets, and other price
              sources here.
            </p>
          </div>
          <MarketsPrimaryButtons />
        </div>
        <MarketsTable
          data={data?.data ?? []}
          pageCount={data?.last_page ?? 0}
          isLoading={isLoading}
          search={search}
          navigate={navigate}
        />
      </Main>

      <MarketsDialogs />
    </MarketsProvider>
  )
}
