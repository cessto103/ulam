import { getRouteApi } from '@tanstack/react-router'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import { PaymentsTable } from './components/payments-table'
import { usePaymentsQuery } from './hooks/use-payments'

const route = getRouteApi('/_authenticated/payments/')

export function Payments() {
  const search = route.useSearch()
  const navigate = route.useNavigate()
  const { data, isLoading } = usePaymentsQuery(search)

  return (
    <>
      <Header fixed>
        <Search className='me-auto' />
        <ThemeSwitch />
        <ConfigDrawer />
        <ProfileDropdown />
      </Header>

      <Main className='flex flex-1 flex-col gap-4 sm:gap-6'>
        <div className='flex flex-wrap items-end justify-between gap-2'>
          <div>
            <h2 className='text-2xl font-bold tracking-tight'>Payments</h2>
            <p className='text-muted-foreground'>
              Premium subscription payments recorded from PayMongo. Read-only
              ledger.
            </p>
          </div>
        </div>
        <PaymentsTable
          data={data?.data ?? []}
          pageCount={data?.last_page ?? 0}
          isLoading={isLoading}
          search={search}
          navigate={navigate}
        />
      </Main>
    </>
  )
}
