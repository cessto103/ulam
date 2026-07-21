import { getRouteApi } from '@tanstack/react-router'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import { InvoicesDialogs } from './components/invoices-dialogs'
import { InvoicesPrimaryButtons } from './components/invoices-primary-buttons'
import { InvoicesProvider } from './components/invoices-provider'
import { InvoicesTable } from './components/invoices-table'
import { useInvoicesQuery } from './hooks/use-invoices'

const route = getRouteApi('/_authenticated/invoices/')

export function Invoices() {
  const search = route.useSearch()
  const navigate = route.useNavigate()
  const { data, isLoading } = useInvoicesQuery(search)

  return (
    <InvoicesProvider>
      <Header fixed>
        <Search className='me-auto' />
        <ThemeSwitch />
        <ConfigDrawer />
        <ProfileDropdown />
      </Header>

      <Main className='flex flex-1 flex-col gap-4 sm:gap-6'>
        <div className='flex flex-wrap items-end justify-between gap-2'>
          <div>
            <h2 className='text-2xl font-bold tracking-tight'>Invoices</h2>
            <p className='text-muted-foreground'>
              Bill advertisers for Sponsored Ads placements. Draft first, mark as paid once payment
              arrives — that's the moment it gets a real, permanent invoice number.
            </p>
          </div>
          <InvoicesPrimaryButtons />
        </div>
        <InvoicesTable
          data={data?.data ?? []}
          pageCount={data?.last_page ?? 0}
          isLoading={isLoading}
          search={search}
          navigate={navigate}
        />
      </Main>

      <InvoicesDialogs />
    </InvoicesProvider>
  )
}
