import { getRouteApi } from '@tanstack/react-router'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import { SponsoredAdsDialogs } from './components/sponsored-ads-dialogs'
import { SponsoredAdsPrimaryButtons } from './components/sponsored-ads-primary-buttons'
import { SponsoredAdsProvider } from './components/sponsored-ads-provider'
import { SponsoredAdsTable } from './components/sponsored-ads-table'
import { useSponsoredAdsQuery } from './hooks/use-sponsored-ads'

const route = getRouteApi('/_authenticated/sponsored-ads/')

export function SponsoredAds() {
  const search = route.useSearch()
  const navigate = route.useNavigate()
  const { data, isLoading } = useSponsoredAdsQuery(search)

  return (
    <SponsoredAdsProvider>
      <Header fixed>
        <Search className='me-auto' />
        <ThemeSwitch />
        <ConfigDrawer />
        <ProfileDropdown />
      </Header>

      <Main className='flex flex-1 flex-col gap-4 sm:gap-6'>
        <div className='flex flex-wrap items-end justify-between gap-2'>
          <div>
            <h2 className='text-2xl font-bold tracking-tight'>Sponsored Ads</h2>
            <p className='text-muted-foreground'>
              Manage paid third-party product placements shown in the Recipe
              and Community feeds.
            </p>
          </div>
          <SponsoredAdsPrimaryButtons />
        </div>
        <SponsoredAdsTable
          data={data?.data ?? []}
          pageCount={data?.last_page ?? 0}
          isLoading={isLoading}
          search={search}
          navigate={navigate}
        />
      </Main>

      <SponsoredAdsDialogs />
    </SponsoredAdsProvider>
  )
}
