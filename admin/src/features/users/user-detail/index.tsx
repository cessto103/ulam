import { useState } from 'react'
import { getRouteApi } from '@tanstack/react-router'
import { ArrowLeft, Ban } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import { useUserQuery } from '../hooks/use-users'
import { UserContentTab } from '../components/user-content-tab'
import { UserModerationTab } from '../components/user-moderation-tab'
import { UserMonetizationTab } from '../components/user-monetization-tab'
import { UserOverviewTab } from '../components/user-overview-tab'
import { UserSecurityTab } from '../components/user-security-tab'

const API_ORIGIN = (import.meta.env.VITE_API_URL as string | undefined)?.replace(/\/api\/?$/, '') ?? ''

const route = getRouteApi('/_authenticated/users/$userId')

export function UserDetailPage() {
  const { userId } = route.useParams()
  const navigate = route.useNavigate()
  const { data: user, isLoading } = useUserQuery(Number(userId))
  const [tab, setTab] = useState('overview')

  return (
    <>
      <Header fixed>
        <Search className='me-auto' />
        <ThemeSwitch />
        <ConfigDrawer />
        <ProfileDropdown />
      </Header>

      <Main className='flex flex-1 flex-col gap-4 sm:gap-6'>
        <div className='flex items-center justify-between gap-2'>
          <Button variant='ghost' size='sm' onClick={() => navigate({ to: '/users' })}>
            <ArrowLeft /> Back to Users
          </Button>
        </div>

        {isLoading || !user ? (
          <p className='text-muted-foreground'>Loading...</p>
        ) : (
          <>
            <Card>
              <CardContent className='flex flex-wrap items-center gap-4 pt-6'>
                <div className='flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-full bg-muted text-lg font-semibold'>
                  {user.avatar ? (
                    <img src={`${API_ORIGIN}${user.avatar}`} alt='' className='h-full w-full object-cover' />
                  ) : (
                    user.name.slice(0, 2).toUpperCase()
                  )}
                </div>
                <div className='flex-1'>
                  <div className='flex items-center gap-2'>
                    <span className='text-base font-semibold'>{user.name}</span>
                    <span className='text-sm text-muted-foreground'>@{user.username}</span>
                  </div>
                  <div className='text-sm text-muted-foreground'>{user.email}</div>
                </div>
                <div className='flex flex-wrap items-center gap-2'>
                  <Badge variant={user.plan === 'premium' ? 'default' : 'outline'} className='capitalize'>
                    {user.plan}
                  </Badge>
                  <Badge variant='outline' className='capitalize'>{user.role}</Badge>
                  {user.banned_at && (
                    <Badge variant='destructive' className='gap-1'>
                      <Ban size={12} /> Banned
                    </Badge>
                  )}
                </div>
              </CardContent>
            </Card>

            <Tabs value={tab} onValueChange={setTab}>
              <TabsList>
                <TabsTrigger value='overview'>Overview</TabsTrigger>
                <TabsTrigger value='content'>Content</TabsTrigger>
                <TabsTrigger value='monetization'>Monetization</TabsTrigger>
                <TabsTrigger value='moderation'>Moderation</TabsTrigger>
                <TabsTrigger value='security'>Security</TabsTrigger>
              </TabsList>
              <TabsContent value='overview' className='mt-4'>
                <UserOverviewTab userId={user.id} />
              </TabsContent>
              <TabsContent value='content' className='mt-4'>
                <UserContentTab userId={user.id} />
              </TabsContent>
              <TabsContent value='monetization' className='mt-4'>
                <UserMonetizationTab userId={user.id} />
              </TabsContent>
              <TabsContent value='moderation' className='mt-4'>
                <UserModerationTab userId={user.id} />
              </TabsContent>
              <TabsContent value='security' className='mt-4'>
                <UserSecurityTab userId={user.id} />
              </TabsContent>
            </Tabs>
          </>
        )}
      </Main>
    </>
  )
}
