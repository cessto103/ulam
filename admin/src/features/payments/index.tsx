import { useState } from 'react'
import { getRouteApi } from '@tanstack/react-router'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import { type Payment } from './data/schema'
import { PaymentsTable } from './components/payments-table'
import { RefundPaymentDialog } from './components/refund-payment-dialog'
import { usePaymentsQuery } from './hooks/use-payments'

const route = getRouteApi('/_authenticated/payments/')

export function Payments() {
  const search = route.useSearch()
  const navigate = route.useNavigate()
  const { data, isLoading } = usePaymentsQuery(search)
  const [refunding, setRefunding] = useState<Payment | null>(null)

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
              All payments (consumer premium and seller subscriptions),
              processed via PayMongo. Refund a PayMongo payment directly from
              its row.
            </p>
          </div>
        </div>
        <PaymentsTable
          data={data?.data ?? []}
          pageCount={data?.last_page ?? 0}
          isLoading={isLoading}
          search={search}
          navigate={navigate}
          onRefund={setRefunding}
        />
      </Main>

      <RefundPaymentDialog
        payment={refunding}
        onOpenChange={(open) => !open && setRefunding(null)}
      />
    </>
  )
}
