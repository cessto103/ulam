import { getRouteApi } from '@tanstack/react-router'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import { WebhooksTable } from './components/webhooks-table'
import { useWebhooksQuery } from './hooks/use-webhooks'

const route = getRouteApi('/_authenticated/webhooks/')

export function Webhooks() {
  const search = route.useSearch()
  const navigate = route.useNavigate()
  const { data, isLoading } = useWebhooksQuery(search)

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
            <h2 className='text-2xl font-bold tracking-tight'>Webhooks</h2>
            <p className='text-muted-foreground'>
              Incoming PayMongo webhook events and how they were processed.
              A "Failed" status here (with too many in a row) is what gets a
              webhook disabled on PayMongo's side.
            </p>
          </div>
        </div>
        <WebhooksTable
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
