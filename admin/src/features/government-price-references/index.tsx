import { getRouteApi } from '@tanstack/react-router'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import { GovernmentPriceReferencesDialogs } from './components/government-price-references-dialogs'
import { GovernmentPriceReferencesPrimaryButtons } from './components/government-price-references-primary-buttons'
import { GovernmentPriceReferencesProvider } from './components/government-price-references-provider'
import { GovernmentPriceReferencesTable } from './components/government-price-references-table'
import { useGovernmentPriceReferencesQuery } from './hooks/use-government-price-references'

const route = getRouteApi('/_authenticated/government-price-references/')

export function GovernmentPriceReferences() {
  const search = route.useSearch()
  const navigate = route.useNavigate()
  const { data, isLoading } = useGovernmentPriceReferencesQuery(search)

  return (
    <GovernmentPriceReferencesProvider>
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
              Government Price References
            </h2>
            <p className='text-muted-foreground'>
              Manage DA Bantay Presyo and DTI SRP reference prices.
            </p>
          </div>
          <GovernmentPriceReferencesPrimaryButtons />
        </div>
        <GovernmentPriceReferencesTable
          data={data?.data ?? []}
          pageCount={data?.last_page ?? 0}
          isLoading={isLoading}
          search={search}
          navigate={navigate}
        />
      </Main>

      <GovernmentPriceReferencesDialogs />
    </GovernmentPriceReferencesProvider>
  )
}
