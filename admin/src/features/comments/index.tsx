import { getRouteApi } from '@tanstack/react-router'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import { CommentsDialogs } from './components/comments-dialogs'
import { CommentsProvider } from './components/comments-provider'
import { CommentsTable } from './components/comments-table'
import { useCommentsQuery } from './hooks/use-comments'

const route = getRouteApi('/_authenticated/comments/')

export function Comments() {
  const search = route.useSearch()
  const navigate = route.useNavigate()
  const { data, isLoading } = useCommentsQuery(search)

  return (
    <CommentsProvider>
      <Header fixed>
        <Search className='me-auto' />
        <ThemeSwitch />
        <ConfigDrawer />
        <ProfileDropdown />
      </Header>

      <Main className='flex flex-1 flex-col gap-4 sm:gap-6'>
        <div className='flex flex-wrap items-end justify-between gap-2'>
          <div>
            <h2 className='text-2xl font-bold tracking-tight'>Comments</h2>
            <p className='text-muted-foreground'>
              Moderate comments left on community posts.
            </p>
          </div>
        </div>
        <CommentsTable
          data={data?.data ?? []}
          pageCount={data?.last_page ?? 0}
          isLoading={isLoading}
          search={search}
          navigate={navigate}
        />
      </Main>

      <CommentsDialogs />
    </CommentsProvider>
  )
}
