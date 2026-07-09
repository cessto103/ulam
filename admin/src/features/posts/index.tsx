import { getRouteApi } from '@tanstack/react-router'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import { PostsDialogs } from './components/posts-dialogs'
import { PostsProvider } from './components/posts-provider'
import { PostsTable } from './components/posts-table'
import { usePostsQuery } from './hooks/use-posts'

const route = getRouteApi('/_authenticated/posts/')

export function Posts() {
  const search = route.useSearch()
  const navigate = route.useNavigate()
  const { data, isLoading } = usePostsQuery(search)

  return (
    <PostsProvider>
      <Header fixed>
        <Search className='me-auto' />
        <ThemeSwitch />
        <ConfigDrawer />
        <ProfileDropdown />
      </Header>

      <Main className='flex flex-1 flex-col gap-4 sm:gap-6'>
        <div className='flex flex-wrap items-end justify-between gap-2'>
          <div>
            <h2 className='text-2xl font-bold tracking-tight'>Posts</h2>
            <p className='text-muted-foreground'>
              Moderate community posts shared from the mobile app.
            </p>
          </div>
        </div>
        <PostsTable
          data={data?.data ?? []}
          pageCount={data?.last_page ?? 0}
          isLoading={isLoading}
          search={search}
          navigate={navigate}
        />
      </Main>

      <PostsDialogs />
    </PostsProvider>
  )
}
