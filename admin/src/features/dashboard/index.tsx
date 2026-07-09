import {
  Ban,
  ChefHat,
  Coins,
  MessagesSquare,
  Sparkles,
  UserCheck,
  Users,
} from 'lucide-react'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import { XpLeaderboard } from './components/xp-leaderboard'
import { useDashboardStats } from './hooks/use-dashboard'

export function Dashboard() {
  const { data: stats, isLoading } = useDashboardStats()

  const tiles = [
    {
      title: 'Total Users',
      value: stats?.users.total,
      icon: Users,
    },
    {
      title: 'Active Today',
      value: stats?.users.active_today,
      icon: UserCheck,
      description: 'Opened the app today',
    },
    {
      title: 'Premium Users',
      value: stats?.users.premium,
      icon: Coins,
      description: stats
        ? `₱${stats.users.estimated_mrr.toLocaleString()} est. MRR`
        : undefined,
    },
    {
      title: 'Banned Users',
      value: stats?.users.banned,
      icon: Ban,
    },
    {
      title: 'Total Posts',
      value: stats?.content.total_posts,
      icon: MessagesSquare,
    },
    {
      title: 'Total Recipes',
      value: stats?.content.total_recipes,
      icon: ChefHat,
    },
  ]

  return (
    <>
      <Header>
        <Search className='me-auto' />
        <ThemeSwitch />
        <ConfigDrawer />
        <ProfileDropdown />
      </Header>

      <Main>
        <div className='mb-2 flex items-center justify-between space-y-2'>
          <h1 className='text-2xl font-bold tracking-tight'>Dashboard</h1>
        </div>

        <div className='grid gap-4 sm:grid-cols-2 lg:grid-cols-3'>
          {tiles.map((tile) => (
            <Card key={tile.title}>
              <CardHeader className='flex flex-row items-center justify-between space-y-0 pb-2'>
                <CardTitle className='text-sm font-medium'>
                  {tile.title}
                </CardTitle>
                <tile.icon className='h-4 w-4 text-muted-foreground' />
              </CardHeader>
              <CardContent>
                <div className='text-2xl font-bold'>
                  {isLoading ? '—' : (tile.value?.toLocaleString() ?? 0)}
                </div>
                {tile.description && (
                  <p className='text-xs text-muted-foreground'>
                    {tile.description}
                  </p>
                )}
              </CardContent>
            </Card>
          ))}
        </div>

        <div className='mt-4 grid grid-cols-1 gap-4 lg:grid-cols-7'>
          <Card className='col-span-1 lg:col-span-4'>
            <CardHeader>
              <CardTitle className='flex items-center gap-2'>
                <Sparkles className='h-4 w-4' /> AI Meal Plan Usage (this month)
              </CardTitle>
              <CardDescription>{stats?.ai_usage.note}</CardDescription>
            </CardHeader>
            <CardContent>
              {isLoading || !stats ? (
                <p className='text-sm text-muted-foreground'>Loading...</p>
              ) : (
                <div className='grid grid-cols-1 gap-4 sm:grid-cols-3'>
                  <div>
                    <p className='text-xs text-muted-foreground'>
                      Meal plans generated
                    </p>
                    <p className='text-xl font-bold'>
                      {stats.ai_usage.meal_plans_this_month.toLocaleString()}
                    </p>
                  </div>
                  <div>
                    <p className='text-xs text-muted-foreground'>
                      Tokens used
                    </p>
                    <p className='text-xl font-bold'>
                      {(
                        stats.ai_usage.prompt_tokens +
                        stats.ai_usage.completion_tokens
                      ).toLocaleString()}
                    </p>
                    <p className='text-xs text-muted-foreground'>
                      {stats.ai_usage.prompt_tokens.toLocaleString()} in /{' '}
                      {stats.ai_usage.completion_tokens.toLocaleString()} out
                    </p>
                  </div>
                  <div>
                    <p className='text-xs text-muted-foreground'>
                      Est. AI cost
                    </p>
                    <p className='text-xl font-bold'>
                      ${stats.ai_usage.estimated_cost.toFixed(2)}
                    </p>
                  </div>
                </div>
              )}
            </CardContent>
          </Card>
          <Card className='col-span-1 lg:col-span-3'>
            <CardHeader>
              <CardTitle>Top by XP</CardTitle>
              <CardDescription>Leaderboard — top 10 users.</CardDescription>
            </CardHeader>
            <CardContent>
              <XpLeaderboard />
            </CardContent>
          </Card>
        </div>
      </Main>
    </>
  )
}
