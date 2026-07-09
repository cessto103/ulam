import { getRouteApi } from '@tanstack/react-router'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import { RecipesDialogs } from './components/recipes-dialogs'
import { RecipesPrimaryButtons } from './components/recipes-primary-buttons'
import { RecipesProvider } from './components/recipes-provider'
import { RecipesTable } from './components/recipes-table'
import { useRecipesQuery } from './hooks/use-recipes'

const route = getRouteApi('/_authenticated/recipes/')

export function Recipes() {
  const search = route.useSearch()
  const navigate = route.useNavigate()
  const { data, isLoading } = useRecipesQuery(search)

  return (
    <RecipesProvider>
      <Header fixed>
        <Search className='me-auto' />
        <ThemeSwitch />
        <ConfigDrawer />
        <ProfileDropdown />
      </Header>

      <Main className='flex flex-1 flex-col gap-4 sm:gap-6'>
        <div className='flex flex-wrap items-end justify-between gap-2'>
          <div>
            <h2 className='text-2xl font-bold tracking-tight'>Recipe List</h2>
            <p className='text-muted-foreground'>
              Manage recipes, budget tags, and ingredients here.
            </p>
          </div>
          <RecipesPrimaryButtons />
        </div>
        <RecipesTable
          data={data?.data ?? []}
          pageCount={data?.last_page ?? 0}
          isLoading={isLoading}
          search={search}
          navigate={navigate}
        />
      </Main>

      <RecipesDialogs />
    </RecipesProvider>
  )
}
