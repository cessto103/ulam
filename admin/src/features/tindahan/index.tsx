import { getRouteApi } from '@tanstack/react-router'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import { TindahanDialogs } from './components/tindahan-dialogs'
import { TindahanPrimaryButtons } from './components/tindahan-primary-buttons'
import { TindahanProvider } from './components/tindahan-provider'
import { TindahanTable } from './components/tindahan-table'
import { useTindahanQuery } from './hooks/use-tindahan'

const route = getRouteApi('/_authenticated/tindahan/')

export function Tindahan() {
  const search = route.useSearch()
  const navigate = route.useNavigate()
  const { data, isLoading } = useTindahanQuery(search)

  return (
    <TindahanProvider>
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
              Stores &amp; Stalls
            </h2>
            <p className='text-muted-foreground'>
              Manage tindahan, sari-sari stores, and market stalls here.
            </p>
          </div>
          <TindahanPrimaryButtons />
        </div>
        <TindahanTable
          data={data?.data ?? []}
          pageCount={data?.last_page ?? 0}
          isLoading={isLoading}
          search={search}
          navigate={navigate}
        />
      </Main>

      <TindahanDialogs />
    </TindahanProvider>
  )
}
