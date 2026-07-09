import { getRouteApi } from '@tanstack/react-router'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import { MarketPricesDialogs } from './components/market-prices-dialogs'
import { MarketPricesPrimaryButtons } from './components/market-prices-primary-buttons'
import { MarketPricesProvider } from './components/market-prices-provider'
import { MarketPricesTable } from './components/market-prices-table'
import { useMarketPricesQuery } from './hooks/use-market-prices'

const route = getRouteApi('/_authenticated/market-prices/')

export function MarketPrices() {
  const search = route.useSearch()
  const navigate = route.useNavigate()
  const { data, isLoading } = useMarketPricesQuery(search)

  return (
    <MarketPricesProvider>
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
              Market Prices
            </h2>
            <p className='text-muted-foreground'>
              Manage tracked item prices across markets and tindahan here.
            </p>
          </div>
          <MarketPricesPrimaryButtons />
        </div>
        <MarketPricesTable
          data={data?.data ?? []}
          pageCount={data?.last_page ?? 0}
          isLoading={isLoading}
          search={search}
          navigate={navigate}
        />
      </Main>

      <MarketPricesDialogs />
    </MarketPricesProvider>
  )
}
